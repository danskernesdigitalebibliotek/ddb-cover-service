# Azure kubernetes service
This document explains how to configure AKS to run Cover Service in Kubernetes with virtual node scaling and monitoring.

Log in through CLI and list available regions. You should use a region in the EU to ensure data safety.
```sh
az login
az account list-locations -o table
```

Set basic configuration variables used in this guide more than once. They are only set in the current terminal window 
used and only as long as it is not closed.
```sh
ksname=ddb-cover-service
res=CoverService 
region=westeurope
version=$(az aks get-versions -l ${region} --query 'orchestrators[-1].orchestratorVersion' -o tsv)
```

## Networking
To enable network policies inside the cluster we need to create custom network.

```sh
vnetname=CoverServiceVnet
vnetsubname=CoverServiceSubnet
```

```sh
az network vnet create \
    --resource-group $res \
    --name $vnetname \
    --address-prefixes 10.0.0.0/8 \
    --subnet-name $vnetsubname \
    --subnet-prefix 10.240.0.0/16
```

Create a service principal and read in the application ID
```sh
SP=$(az ad sp create-for-rbac --name ${res} --output json)
SP_ID=$(echo $SP | jq -r .appId)
SP_PASSWORD=$(echo $SP | jq -r .password)
```

Get the virtual network resource ID
```sh
VNET_ID=$(az network vnet show --resource-group $res --name $vnetname --query id -o tsv)
```

Assign the service principal Contributor permissions to the virtual network resource
```sh
az role assignment create --assignee $SP_ID --scope $VNET_ID --role Contributor
```

Get the virtual network subnet resource ID
```sh
SUBNET_ID=$(az network vnet subnet show --resource-group $res --vnet-name $vnetname --name $vnetsubname --query id -o tsv)
```



## Create services, and the cluster

Create the cluster and wait for it to create the 3 nodes in the standard cluster (it takes a bit of time, so go ahead 
and get yourself a cup of coffee).

```sh
az aks create \
    --resource-group ${res} \
    --name ${ksname} \
    --node-count 3 \
    --node-vm-size Standard_DS3_v2 \
    --kubernetes-version ${version} \
    --network-plugin kubenet \
    --service-cidr 10.0.0.0/16 \
    --dns-service-ip 10.0.0.10 \
    --docker-bridge-address 172.17.0.1/16 \
    --vnet-subnet-id $SUBNET_ID \
    --service-principal $SP_ID \
    --client-secret $SP_PASSWORD \
    --network-policy calico
```

Configure kubectl to connect to the new cluster
```sh
az aks get-credentials --resource-group ${res} --name ${ksname}
```

Verify that you are connected to the cluster now.
```sh
kubectl get nodes
```

### Storage account (Only if you are using azure-files)

```sh
AKS_PERS_STORAGE_ACCOUNT_NAME=coverservice
AKS_PERS_RESOURCE_GROUP=CoverService
AKS_PERS_LOCATION=westeurope
AKS_PERS_SHARE_NAME=coverservice
```

Create a storage account
```sh
az storage account create -n $AKS_PERS_STORAGE_ACCOUNT_NAME -g $AKS_PERS_RESOURCE_GROUP -l $AKS_PERS_LOCATION --sku Premium_LRS --kind FileStorage
```

Get storage account key
```sh
STORAGE_KEY=$(az storage account keys list --resource-group $AKS_PERS_RESOURCE_GROUP --account-name $AKS_PERS_STORAGE_ACCOUNT_NAME --query "[0].value" -o tsv)
```

Create cluster secret to access storage account.
```sh
kubectl create secret generic azure-secret --from-literal=azurestorageaccountname=$AKS_PERS_STORAGE_ACCOUNT_NAME --from-literal=azurestorageaccountkey=$STORAGE_KEY
```

## Helm
We are going to use https://helm.sh/ to install ingress and cert-manager into the cluster setup. Note that we here are using helm version 3. We also install the kubectx helper tool as it makes switching cluster and namespaces easier.
```sh
brew install helm
brew install kubectx
```

Add stable official and bitnami helm charts repositories.
```sh
helm repo add bitnami https://charts.bitnami.com/bitnami
helm repo add ingress-nginx https://kubernetes.github.io/ingress-nginx
helm repo update
```

## Ingress

Get the resource group create to hold cluster related resources for your cluster
```sh
mcres=$(az aks show --resource-group $res --name $ksname --query nodeResourceGroup -o tsv)
```

Create static public IP.
```
az network public-ip create \
--resource-group $mcres \
--name CoverServicePublicIP \
--sku Standard \
--allocation-method static \
--query publicIp.ipAddress -o tsv
```

Copy the ip outputted and set it into an variable
```sh
EXTERNAL_IP=XX.XX.XX.XX
```


Create namespace and change into the namespace.
```sh
kubectl create namespace ingress
```

Install nginx ingress using helm chart.
```sh
helm upgrade --install ingress ingress-nginx/ingress-nginx --namespace ingress \
--set controller.metrics.enabled=true \
--set controller.service.externalTrafficPolicy=Local \
--set controller.service.annotations."service\.beta\.kubernetes\.io/azure-dns-label-name"=$ksname \
--set controller.podAnnotations."prometheus\.io/scrape"="true" \
--set controller.podAnnotations."prometheus\.io/port"="10254" \
--set controller.service.loadBalancerIP=$EXTERNAL_IP
```

### External Traffic Policy
This is only need if you do not set it to local in the install step above. 

To ensure that client ip's are correctly set in headers and forwarded to the nginx backend pod's you need to ensure that
`External Traffic Policy` is changed from `Cluster` to `Local`. Edit the service configuration and change the value for 
`externalTrafficPolicy` to local.   

```
kubectl edit service/ingress-nginx-ingress-controller
```

For more information see https://kubernetes.io/docs/tutorials/services/source-ip/#source-ip-for-services-with-typenodeport

# Certificate manager

Create the namespace for cert-manager
```sh
kubectl create namespace cert-manager
```

Add the Jetstack Helm repository
```sh
helm repo add jetstack https://charts.jetstack.io
```

Update your local Helm chart repository cache
```sh
helm repo update
```

Install the cert-manager Helm chart to enable support for lets-encrypt.
```sh
helm install cert-manager --namespace cert-manager --version v0.16.1 jetstack/cert-manager --set installCRDs=true
```

# Prepare cluster (shard configuration)
The first step is to prepare the cluster with services that are used across the different services that makes up the complete CoverService application (frontend, upload service, faktor export, importers etc.).

```sh
kubectl create namespace cover-service
helm upgrade --install shared-config infrastructure/shared-config --namespace cover-service
```

# Install ElasticSearch

We use https://github.com/bitnami/charts/tree/master/bitnami/elasticsearch to install elaseticsearch into the cluster

```sh
helm upgrade --install es bitnami/elasticsearch --namespace cover-service \
--set image.tag=6.8.12-debian-10-r11 \
--set metrics.enabled=true \
--set master.persistence.enabled=true \
--set master.persistence.storageClass=azuredisk-premium-retain \
--set master.persistence.accessModes[0]=ReadWriteOnce \
--set master.persistence.size=256Gi \
--set master.livenessProbe.enabled=true \
--set master.readinessProbe.enabled=true \
--set master.heapSize=256m \
--set data.persistence.enabled=true \
--set data.persistence.storageClass=azuredisk-premium-retain \
--set data.persistence.accessModes[0]=ReadWriteOnce \
--set data.persistence.size=256Gi \
--set data.livenessProbe.enabled=true \
--set data.readinessProbe.enabled=true \
--set data.heapSize=2048m \
--set coordinating.livenessProbe.enabled=true \
--set coordinating.readinessProbe.enabled=true \
--set volumePermissions.enabled=true \
--set coordinating.replicas=1 \
--set master.replicas=1 \
--set data.replicas=1
```

@TODO: setup curator to clean-up stats.

Elasticsearch can be accessed within the cluster on port `9200` at `cs-elasticsearch-coordinating-only.cover-service.svc.cluster.local`

# Install redis

The application requires redis as cache and queue broker. We use https://github.com/bitnami/charts/tree/master/bitnami/redis chart to install redis.

We need to make some minor configurations changes to Redis this can be done by adding a `values.yaml` that extends the
helm install below with the following content.

```yaml
---
configmap: |-
  maxmemory 250mb
  maxmemory-policy volatile-lfu
```

Install Redis into the cluster.
```sh
helm upgrade --install redis bitnami/redis --namespace cover-service \
--set image.tag=4.0 \
--set global.storageClass=azurefile-premium-retain \
--set usePassword=false \
--set metrics.enabled=true \
--set cluster.enabled=false \
--set master.persistence.accessModes[0]=ReadWriteMany \
--set master.persistence.size=100Gi \
--set volumePermissions.enabled=true \
--set master.disableCommands="" \
-f values.yaml
```

# RabbitMQ
This project uses [RabbitMQ](https://www.rabbitmq.com/) as message broker for queues with symfony messenger.

```sh
helm upgrade --install mq bitnami/rabbitmq --namespace cover-service \
--set image.tag=3.8.9-debian-10-r0 \
--set auth.username=<USERNAME> \
--set auth.password=<PASSWORD> \
--set replicaCount=2 \
--set resources.limits.memory="512Mi" \
--set persistence.enabled=true \
--set persistence.storageClass=azurefile-premium-retain \
--set persistence.accessModes[0]=ReadWriteMany \
--set persistence.size=128Gi \
--set metrics.enabled=true \
--set memoryHighWatermark.enabled="true" \
--set memoryHighWatermark.type="absolute" \
--set memoryHighWatermark.value="512MB"
```

__Note__: That you have to set the username and password in the helm command above.

# Application install
To install the application into the kubernetes cluster helm chars are included with the source code.

### CoverService

Before using the helm chat copy the file `infrastructure/cover-service/templates/secret.example.yaml` into `secret.yaml`
in the same folder and edit the file filling in the missing configuration secrets.

Get the main application up and running.
```sh
helm upgrade --install cover-service infrastructure/cover-service --namespace cover-service --set hpa.enabled=true --set ingress.enableTLS=true --set ingress.domain=cover.dandigbib.org
```

Jump into the new namespace.
```sh
kubens cover-service
```

### The other services

* [Vendor Importers service](https://github.com/danskernesdigitalebibliotek/ddb-cover-service-importers)
* [Upload service](https://github.com/danskernesdigitalebibliotek/ddb-cover-service-upload)
* [Faktor export service](https://github.com/danskernesdigitalebibliotek/ddb-cover-service-faktor-export)

