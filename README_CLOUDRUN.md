# Guía de Despliegue: Google Cloud Run + Supabase (MMOM)

Esta guía explica paso a paso cómo desplegar el backend del **Simulador Estratégico MMOM** en **Google Cloud Run**, conectado a una base de datos PostgreSQL en **Supabase**.

---

## 1. Ventajas de esta combinación

* **Supabase:** Ofrece una base de datos PostgreSQL 15+ administrada, con copias de seguridad automáticas, SSL y panel web para inspeccionar datos en tiempo real.
* **Google Cloud Run:** Ejecuta el backend Laravel en un contenedor Docker serverless (escala a 0 cuando no hay jugadores, ahorrando costos).
* **Conexión simplificada:** Al estar Supabase en la nube con SSL obligatorio, no necesitas configurar VPC Connectors ni proxies de Cloud SQL. La conexión se realiza directamente mediante TCP/SSL (`DB_SSLMODE=require`).

---

## 2. Obtener credenciales de Supabase

1. Entra a tu proyecto en [supabase.com](https://supabase.com).
2. En el menú lateral izquierdo, haz clic en el icono de **Project Settings** (el engranaje) y ve a **Database**.
3. Baja hasta la sección **Connection parameters** y copia los siguientes datos:
   * **Host:** Por ejemplo `db.abcdefghijklm.supabase.co`
   * **Database name:** `postgres`
   * **Port:** `5432`
   * **User:** `postgres`
   * **Password:** La contraseña que creaste al iniciar tu proyecto en Supabase.

> **Tip:** También puedes ir a la sección **Connection String** -> pestaña **URI** y copiar el enlace directo `postgresql://postgres:[PASSWORD]@db.xxxx.supabase.co:5432/postgres`.

---

## 3. Preparación de tu archivo `.env` local (Opcional)

Si deseas probar la conexión a Supabase desde tu máquina antes de desplegar:
En [`mmom-app/.env`](file:///g:/PROYECTOS/mmom/mmom-app/.env):
```env
DB_CONNECTION=pgsql
DB_HOST=db.TU_PROYECTO_REF.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=TU_PASSWORD_DE_SUPABASE
DB_SSLMODE=require
```

Y puedes correr las migraciones localmente hacia Supabase:
```powershell
cd g:\PROYECTOS\mmom\mmom-app
php artisan migrate
```

---

## 4. Despliegue en Google Cloud Run

Desde la carpeta `mmom-app`, puedes desplegar usando una sola línea en PowerShell:

```powershell
cd g:\PROYECTOS\mmom\mmom-app

.\deploy-cloudrun.ps1 `
    -ProjectId "TU_PROJECT_ID_GCP" `
    -SupabaseHost "db.TU_PROYECTO_REF.supabase.co" `
    -SupabasePassword "TU_PASSWORD_DE_SUPABASE"
```

O si prefieres el comando directo de `gcloud`:

```powershell
gcloud run deploy mmom-backend `
    --source . `
    --region us-central1 `
    --platform managed `
    --allow-unauthenticated `
    --set-env-vars "APP_NAME=MMOM,APP_ENV=production,APP_DEBUG=false,APP_KEY=base64:TU_KEY_LOCAL,LOG_CHANNEL=stderr,DB_CONNECTION=pgsql,DB_HOST=db.TU_PROYECTO_REF.supabase.co,DB_PORT=5432,DB_DATABASE=postgres,DB_USERNAME=postgres,DB_PASSWORD=TU_PASSWORD_DE_SUPABASE,DB_SSLMODE=require,RUN_MIGRATIONS=true,SESSION_DRIVER=database,CACHE_STORE=database"
```

---

## 5. ¿Qué ocurre durante el despliegue?

1. **Cloud Build** recibe el código fuente y compila la imagen Docker en Google Cloud (no necesitas tener Docker activo en tu PC).
2. **Cloud Run** levanta la instancia y le asigna el puerto dinámico.
3. El script de entrada (`docker-entrypoint.sh`) detecta `RUN_MIGRATIONS=true` y ejecuta automáticamente:
   * Creación de esquemas: `identity`, `catalog`, `game`, `market`, `operations`, `inventory`, `commerce`, `finance`, `simulation`, `analytics`, `audit`.
   * Carga de catálogos iniciales y tablas de partidas/decisiones de la Etapa E2.
4. Cloud Run imprime la URL pública HTTPS de tu backend (por ejemplo: `https://mmom-backend-xxxx-uc.a.run.app`).

---

## 6. Verificación en Supabase

Una vez desplegado:
1. Abre tu panel de Supabase y ve a **Table Editor**.
2. En el selector de esquema (por defecto dice `public`), cambia a `game`, `catalog` o `identity`.
3. Verás todas las tablas del simulador MMOM creadas con sus catálogos listos.
