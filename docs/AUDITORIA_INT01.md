# Auditoría técnica MMOM-INT-01 (revisión estática)

## Hallazgos corregidos en este paquete
1. **Conexiones de migración E2–E6:** las migraciones ejecutaban `DB::unprepared` sobre la conexión por defecto. Ahora ejecutan explícitamente `DB::connection('pgsql')->unprepared`; E1 ya lo hacía. Se configuró lectura de SQL con fallo explícito si el archivo falta.
2. **Rutas E2:** eliminada la aplicación redundante del grupo `web` al incluir `routes/mmom_e2.php` desde `routes/web.php`; incorporado limitador `throttle:60,1` al grupo autenticado.
3. **Procedimiento de copia:** instalador PowerShell que detecta colisiones, se niega a sobrescribir por defecto, conserva respaldo si se autoriza sobrescritura y agrega inclusiones E2/E7 una sola vez.
4. **Verificación reproducible:** scripts para comprobación de comandos Artisan y SQL de catálogos/esquemas posteriores a migración.
5. **Autenticación:** instrucciones explícitas para que el proveedor Eloquent apunte a `App\Models\MmomUser` en `config/auth.php` (sin presumir Fortify instalado).

## Bloqueadores vigentes (NO corregidos)
- No existe aplicación Laravel completa (sin `artisan`, `composer.json`/`vendor`) dentro del ZIP E8: es un overlay.
- No se verificó migración E1-E6 con un PostgreSQL real; **no ejecutar en producción**. Se requiere base de desarrollo vacía.
- Autenticación Fortify y MFA reales no integrados ni validados; verificar inicio de sesión y administración tras completar instalación.
- Las operaciones E3, mercado E4, PALO E5, MIFIV E6 y UI E7 **no forman un flujo integrado de partida**. No ejecutar con usuarios reales ni publicar clasificaciones.
- E4 solo genera previsualizaciones; E6 conserva candidatos, no cierre oficial atómico.
- No se ha comprobado con pruebas dinámicas protección contra ataques, concurrencia, recuperación, WCAG, cargas ni ocho rondas completas.
- Paquete no es un instalador autónomo ni debe instalarse sobre base de datos con contenido sin un plan de migración.

## Riesgo a vigilar: migraciones Laravel estándar
Las migraciones predeterminadas de Laravel crean `users` en `public`; MMOM usa `identity.users`. La coexistencia no implica migración de credenciales: el proveedor de autenticación debe apuntar al modelo correcto. Revisar también las tablas de sesiones/colas/reset de contraseñas utilizadas por Fortify.

## Criterio de cierre de INT-01
Laravel 13 instalado; migraciones E1-E6 ejecutadas sin error en PostgreSQL; siete discotecas y cuatro segmentos comprobados; inicio de sesión real validado, MFA administrativo validado, rutas autorizadas comprobadas; reporte de pruebas y bloqueadores firmado. La integración de una partida íntegra E2-E7 se planifica a continuación y no se declara conseguida por INT-01 V0.1.
