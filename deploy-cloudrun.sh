#!/bin/bash
set -e

PROJECT_ID=${1:-$(gcloud config get-value project 2>/dev/null)}
SUPABASE_HOST=$2
SUPABASE_PASSWORD=$3
REGION=${4:-"us-central1"}
SERVICE_NAME=${5:-"mmom-backend"}

if [ -z "$PROJECT_ID" ]; then
    echo "Error: Debes proporcionar el ID del proyecto GCP."
    echo "Uso: ./deploy-cloudrun.sh <PROJECT_ID> [SUPABASE_HOST] [SUPABASE_PASSWORD] [REGION] [SERVICE_NAME]"
    exit 1
fi

APP_KEY=$(grep "^APP_KEY=" .env 2>/dev/null | cut -d '=' -f2-)
if [ -z "$APP_KEY" ]; then
    APP_KEY="base64:$(openssl rand -base64 32)"
fi

echo "========================================================="
echo " Desplegando MMOM en Google Cloud Run + Supabase"
echo "========================================================="
echo "Proyecto GCP: $PROJECT_ID"
echo "Región:       $REGION"

ENV_VARS="APP_NAME=MMOM,APP_ENV=production,APP_DEBUG=false,APP_KEY=$APP_KEY,LOG_CHANNEL=stderr,DB_CONNECTION=pgsql,DB_DATABASE=postgres,DB_PORT=5432,DB_USERNAME=postgres,DB_SSLMODE=require,RUN_MIGRATIONS=true,SESSION_DRIVER=database,CACHE_STORE=database"

if [ -n "$SUPABASE_HOST" ]; then
    ENV_VARS="$ENV_VARS,DB_HOST=$SUPABASE_HOST"
fi
if [ -n "$SUPABASE_PASSWORD" ]; then
    ENV_VARS="$ENV_VARS,DB_PASSWORD=$SUPABASE_PASSWORD"
fi

gcloud run deploy "$SERVICE_NAME" \
    --source . \
    --project "$PROJECT_ID" \
    --region "$REGION" \
    --platform managed \
    --allow-unauthenticated \
    --set-env-vars "$ENV_VARS"

echo "Despliegue finalizado con éxito."
