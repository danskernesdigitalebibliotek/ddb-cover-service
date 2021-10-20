# Cover Service on Azure kubernetes service
This document explains how to configure AKS to run Cover Service in Kubernetes with virtual node scaling and monitoring.

## Requirements
This guide assumes you have the following tools installed in your shell  
`az` - azure cli  
`jq` - tool for processing JSON input  
`kubectl` - controls the Kubernetes cluster manager  
`helm` - the package manager for Kubernetes (https://helm.sh/docs/)  
`kubectx` - a utility to manage and switch between kubectl contexts

All can be installed using `brew install [name]` or similar.

## Naming Conventions

### Shell
[Google's Shell Style Guide](https://google.github.io/styleguide/shellguide.html#s7-naming-conventions):  
**Variable Names:** Lower-case, with underscores to separate words. Ex: `my_variable_name`  
**Constants and Environment Variable Names:** All caps, separated with underscores, declared
at the top of the file. Ex: `MY_CONSTANT`

### Kubernetes
[Object Names and IDs](https://kubernetes.io/docs/concepts/overview/working-with-objects/names/)  
Most resource types require a name that can be used as a DNS subdomain name as defined in RFC 1123. This means the name 
must:
* contain no more than 253 characters
* contain only lowercase alphanumeric characters, '-' or '.'
* start with an alphanumeric character
* end with an alphanumeric character

### Helm
[Values](https://helm.sh/docs/chart_best_practices/values/)  
Variable names should begin with a lowercase letter, and words should be separated with camelcase

## Authentication
Log in through CLI and list available regions. You should use a region in the EU to ensure data safety.
```sh
az login
az account list-locations -o table
```

If you have access to multiple accounts ensure you are on the right account/subscription by doing
```sh
az account list
```
Look for 
```json
"isDefault": true,
```
To switch default subscription
```sh
az account set --subscription "[account_name]"
```

## Basic Configuration
Set basic configuration options as shell variables. They are only set in the current terminal window and 
are cleared when the window is closed.
```sh
# The Azure name of the managed cluster
az_cluster_name=ddb-cover-service
# The Azure resource group to attach the cluster to
az_resource_group=CoverService 
# The Azure geographical region where the cluster should be located 
az_region=westeurope
# The kubernetes version to use. Set by getting the latest version available in the geographical region
az_kubernetes_version=$(az aks get-versions -l ${az_region} --query 'orchestrators[-1].orchestratorVersion' -o tsv)
```

## Create the resource group
Check if the resource group already exists:
```sh
az group exists --name ${az_resource_group}
```
If it does exist, consider if this is the correct group to use.  

To create the resource group if it doesn't exist:
```sh
az group create --location ${az_region} --name ${az_resource_group}
```

## Networking
To enable network policies inside the cluster we need to create a custom network.

```sh
# The Azure virtual network name 
az_virtual_network_name=${az_cluster_name}-vnet
# The Azure virtual subnet name
az_virtual_subnet_name=${az_cluster_name}-subnet
```

```sh
az network vnet create \
    --resource-group $az_resource_group \
    --name $az_virtual_network_name \
    --address-prefixes 10.0.0.0/8 \
    --subnet-name $az_virtual_subnet_name \
    --subnet-prefix 10.240.0.0/16
```

Create a service principal and read in the application ID
```sh
az_service_principal_json_object=$(az ad sp create-for-rbac --name ${az_resource_group} --output json)
az_service_principal_id=$(echo $az_service_principal_json_object | jq -r .appId)
az_service_principal_password=$(echo $az_service_principal_json_object | jq -r .password)
```

Get the virtual network resource ID
```sh
az_virtual_network_id=$(az network vnet show --resource-group $az_resource_group --name $az_virtual_network_name --query id -o tsv)
```

Assign the service principal Contributor permissions to the virtual network resource
```sh
az role assignment create --assignee $az_service_principal_id --scope $az_virtual_network_id --role Contributor
```

Get the virtual network subnet resource ID
```sh
az_virtual_subnet_id=$(az network vnet subnet show --resource-group $az_resource_group --vnet-name $az_virtual_network_name --name $az_virtual_subnet_name --query id -o tsv)
```

## Create services, and the cluster

Create the cluster and wait for it to create the 3 nodes in the standard cluster (it takes a bit of time, so go ahead 
and get yourself a cup of coffee).

```sh
az aks create \
    --resource-group ${az_resource_group} \
    --name ${az_cluster_name} \
    --node-count 3 \
    --node-vm-size Standard_DS3_v2 \
    --kubernetes-version ${az_kubernetes_version} \
    --network-plugin kubenet \
    --service-cidr 10.0.0.0/16 \
    --dns-service-ip 10.0.0.10 \
    --docker-bridge-address 172.17.0.1/16 \
    --vnet-subnet-id $az_virtual_subnet_id \
    --service-principal $az_service_principal_id \
    --client-secret $az_service_principal_password \
    --network-policy calico
```

Configure kubectl to connect to the new cluster
```sh
az aks get-credentials --resource-group ${az_resource_group} --name ${az_cluster_name}
```

Verify that you are connected to the cluster now.
```sh
kubectl get nodes
```

### Storage account (Only if you are using azure-files)

```sh
az_pers_storage_account_name=coverservice
az_pers_resource_group=CoverService
az_pers_location=westeurope
az_pers_share_name=coverservice
```

Create a storage account
```sh
az storage account create -n $az_pers_storage_account_name -g $az_pers_resource_group -l $az_pers_location --sku Premium_LRS --kind FileStorage
```

Get storage account key
```sh
az_storage_key=$(az storage account keys list --resource-group $az_pers_resource_group --account-name $az_pers_storage_account_name --query "[0].value" -o tsv)
```

Create cluster secret to access storage account.
```sh
kubectl create secret generic azure-secret --from-literal=azurestorageaccountname=$az_pers_storage_account_name --from-literal=azurestorageaccountkey=$az_storage_key
```

## Setup Helm
We use helm to install ingress and the cert-manager into the cluster setup. Note that we here are using helm version 3.

Add stable official and bitnami helm charts repositories:
```sh
helm repo add bitnami https://charts.bitnami.com/bitnami
helm repo add ingress-nginx https://kubernetes.github.io/ingress-nginx
helm repo update
```

## Ingress install & setup

Get the resource group create to hold cluster related resources for your cluster
```sh
az_node_resource_group=$(az aks show --resource-group $az_resource_group --name $az_cluster_name --query nodeResourceGroup -o tsv)
```

Create static public IP.
```
az network public-ip create \
--resource-group $az_node_resource_group \
--name CoverServicePublicIP \
--sku Standard \
--allocation-method static \
--query publicIp.ipAddress -o tsv
```

Copy the ip outputted and set it into an variable
```sh
az_external_ip=XX.XX.XX.XX
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
--set controller.service.annotations."service\.beta\.kubernetes\.io/azure-dns-label-name"=$az_cluster_name \
--set controller.podAnnotations."prometheus\.io/scrape"="true" \
--set controller.podAnnotations."prometheus\.io/port"="10254" \
--set controller.service.loadBalancerIP=$az_external_ip
```

### External Traffic Policy
This is only needed if you do not set it to local in the installation step above. 

To ensure that client ip's are set correctly in http headers and forwarded to the nginx backend pod's you need to ensure 
that `External Traffic Policy` is changed from `Cluster` to `Local`. Edit the service configuration and change the value 
for `externalTrafficPolicy` to local.   

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
kubectl apply -f https://github.com/jetstack/cert-manager/releases/download/v1.5.0/cert-manager.crds.yaml
helm install cert-manager --namespace cert-manager --version v1.5.0 jetstack/cert-manager
```

# Prepare the cluster (shard configuration)
The first step is to prepare the cluster with services that are used across the different services that makes up the 
complete CoverService application (frontend, upload service, faktor export, importers etc.).

```sh
kubectl create namespace cover-service
helm upgrade --install shared-config infrastructure/shared-config --namespace cover-service
```

# Install ElasticSearch

We use https://github.com/bitnami/charts/tree/master/bitnami/elasticsearch to install elaseticsearch into the cluster

```sh
helm upgrade --install es bitnami/elasticsearch --namespace cover-service \
--set image.tag=6.8.20-debian-10-r3 \
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

The application requires redis as cache and queue broker. We use https://github.com/bitnami/charts/tree/master/bitnami/redis 
chart to install redis.

We need to make some minor configurations changes to Redis. This can be done by adding a `values.yaml` that extends the
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
--set replica.replicaCount=0 \
--set auth.enabled=false \
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
helm upgrade \
--install cover-service infrastructure/cover-service \
--namespace cover-service \
--set hpa.enabled=true \
--set ingress.enableTLS=true \
--set ingress.domain=cover.dandigbib.org
```

Jump into the new namespace.
```sh
kubens cover-service
```

### The other services

* [Vendor Importers service](https://github.com/danskernesdigitalebibliotek/ddb-cover-service-importers)
* [Upload service](https://github.com/danskernesdigitalebibliotek/ddb-cover-service-upload)
* [Faktor export service](https://github.com/danskernesdigitalebibliotek/ddb-cover-service-faktor-export)

