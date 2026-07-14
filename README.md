# PHP Nginx Kubernetes Application

This repository contains a containerized PHP application designed to be deployed on Kubernetes. It utilizes a multi-container pod pattern (sidecar pattern) to run PHP-FPM and Nginx together efficiently.

## Architecture

The application is built using the following components:

*   **PHP-FPM (`php:8.5-fpm-alpine`)**: Handles PHP request processing. The Dockerfile includes performance optimizations such as OpCache configuration and tuned FPM pool settings.
*   **Nginx (`nginx:1.27-alpine`)**: Serves as the web server, proxying PHP requests to the PHP-FPM container and serving static files.
*   **Kubernetes Resources**:
    *   **Deployment**: Manages the application pods. It uses an `initContainer` to copy the application code from the PHP image into a shared `emptyDir` volume, making it available to both Nginx and PHP-FPM.
    *   **ConfigMap**: Provides the Nginx virtual host configuration (`default.conf`).
    *   **Service**: Exposes the application via a `NodePort`.
    *   **HorizontalPodAutoscaler (HPA)**: Automatically scales the deployment between 3 and 20 replicas based on CPU (70% utilization target) and Memory (80% utilization target).
    *   **StatefulSet (MariaDB)**: Provides a stateful MariaDB database with a persistent volume claim and headless service on port 3610.

## Prerequisites

Before deploying the application, ensure you have the following installed and configured:

*   [Docker](https://docs.docker.com/get-docker/)
*   [Kubectl](https://kubernetes.io/docs/tasks/tools/) configured to connect to your cluster
*   A running Kubernetes cluster (e.g., Minikube, kind, EKS, GKE, AKS)
*   Metrics Server installed in your cluster (required for HPA to work properly)

## Getting Started

### 1. Build the Docker Image

The Kubernetes deployment expects a local image named `php-app:8.5`. Build the image using the provided `Dockerfile`:

```bash
docker build -t php-app:8.5 .
```

*Note: If you are using a tool like Minikube or kind, you need to load the image into the cluster's local registry. For Minikube, run `eval $(minikube docker-env)` before building, or use `minikube image load php-app:8.5`.*

### 2. Deploy to Kubernetes

Apply the Kubernetes manifests in the following order:

```bash
# 1. Apply the Nginx configuration
kubectl apply -f configmap.yaml

# 2. Deploy the application and the NodePort service
kubectl apply -f deployment-service.yaml

# 3. Apply the Horizontal Pod Autoscaler
kubectl apply -f hpa.yaml

# 4. Deploy the MariaDB StatefulSet
kubectl apply -f mariadb.yaml
```

### 3. Verify the Deployment

Check the status of your resources to ensure everything is running correctly:

```bash
# Check pods
kubectl get pods -l app=php-nginx-app

# Check the service
kubectl get svc nginx-lb

# Check the HPA
kubectl get hpa php-nginx-hpa
```

### 4. Access the Application

Since the service is exposed as a `NodePort`, you can access it via the IP of any node in your cluster and the assigned NodePort.

If you are using Minikube, you can easily get the URL by running:

```bash
minikube service nginx-lb --url
```

When you access the application in your browser, you should see a greeting showing the PHP version and the pod's hostname, followed by the `phpinfo()` output.

## Cleanup

To remove all the resources deployed by this application from your cluster, run:

```bash
kubectl delete -f mariadb.yaml
kubectl delete -f hpa.yaml
kubectl delete -f deployment-service.yaml
kubectl delete -f configmap.yaml
```
