



## Ingress monitoring

```
kubectl patch pod ingress-nginx-ingress-controller --patch "$(cat ingress-patch.yaml)"
```

https://github.com/helm/charts/tree/master/stable/nginx-ingress

controller.metrics.service.annotations

https://github.com/helm/charts/blob/master/stable/nginx-ingress/templates/controller-deployment.yaml#L26
