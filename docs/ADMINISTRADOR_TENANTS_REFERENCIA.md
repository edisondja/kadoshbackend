# Referencia técnica: Administrador de Tenants

Documentación de **tablas, vistas, controladores, modelos, rutas y middleware** del módulo de administración de tenants (acceso con usuario administrador, fuera del sistema multi-tenant).

---

## 1. Tablas de base de datos

Todas las tablas del administrador de tenants viven en la **base de datos maestra** (conexión `mysql` en `.env`), no en las bases de datos de cada tenant.

### 1.1 Tabla `admin_users`

Usuarios que pueden acceder al panel de administración de tenants.

| Columna      | Tipo         | Descripción                          |
|-------------|--------------|--------------------------------------|
| `id`        | int, PK      | Identificador                        |
| `usuario`   | varchar(100) | Login (único)                        |
| `password`  | varchar(255) | Contraseña hasheada (bcrypt)         |
| `nombre`    | varchar(255) | Nombre del administrador             |
| `apellido`  | varchar(255) | Apellido (nullable)                  |
| `activo`    | boolean      | Si puede iniciar sesión (default 1)  |
| `created_at`| timestamp    |                                      |
| `updated_at`| timestamp    |                                      |

- **Migración:** `database/migrations/2026_03_04_100000_create_admin_users_table.php`
- **Conexión:** `mysql` (maestra)

### 1.2 Tabla `tenants`

Registro de cada tenant (clínica/instancia). La usa el panel de administración y el middleware de tenant.

| Columna            | Tipo         | Descripción                          |
|--------------------|--------------|--------------------------------------|
| `id`               | bigint, PK   | Identificador                        |
| `nombre`           | varchar(255) | Nombre descriptivo                   |
| `subdominio`       | varchar(100) | Subdominio único (ej: clinica1)      |
| `database_name`    | varchar(255) | Nombre de la BD del tenant           |
| `fecha_vencimiento`| date, null   | Fecha límite de licencia             |
| `activo`           | boolean      | Si está activo (default 1)            |
| `bloqueado`        | boolean      | Si está bloqueado (default 0)        |
| `notas`            | text, null   | Notas internas                       |
| `contacto_nombre`  | varchar(255), null | Nombre de contacto           |
| `contacto_email`   | varchar(255), null | Email de contacto            |
| `contacto_telefono`| varchar(50), null  | Teléfono de contacto         |
| `created_at`       | timestamp    |                                      |
| `updated_at`       | timestamp    |                                      |

- **Migración:** `database/migrations/2026_01_16_000000_create_tenants_table.php`
- **Conexión:** `mysql` (maestra). El modelo `Tenant` declara `protected $connection = 'mysql';`

---

## 2. Modelos (Eloquent)

| Modelo      | Archivo           | Conexión | Tabla         | Uso                                      |
|------------|-------------------|----------|---------------|------------------------------------------|
| `AdminUser`| `app/AdminUser.php` | `mysql` | `admin_users` | Login del panel de administración        |
| `Tenant`   | `app/Tenant.php`  | `mysql` | `tenants`     | CRUD de tenants y lógica de estado       |

### Detalles

- **AdminUser:** `$hidden = ['password']`, mutator para hashear `password`, método `verificarClave($clave)`.
- **Tenant:** `$fillable` y `$casts` para fechas y booleanos; métodos `estaVencido()`, `puedeAcceder()`, `diasRestantes()`, atributo calculado `estado`.

---

## 3. Controladores

| Controlador              | Archivo                                      | Responsabilidad                          |
|-------------------------|----------------------------------------------|------------------------------------------|
| `ControllerAdminAuth`   | `app/Http/Controllers/ControllerAdminAuth.php` | Login y datos del admin autenticado    |
| `ControllerTenant`      | `app/Http/Controllers/ControllerTenant.php`   | CRUD y verificación de estado de tenants |

### ControllerAdminAuth

- **login(Request)**  
  - Ruta: `POST /api/admin/login`  
  - Body: `usuario`, `clave`  
  - Valida credenciales contra `AdminUser`, genera JWT con `roll = 'admin'`, devuelve `token`, `id`, `nombre`, `apellido`, `usuario`.

- **me(Request)**  
  - Ruta: `GET /api/admin/me` (protegida por middleware `admin`)  
  - Usa `$request->attributes->get('admin_user')` y devuelve datos del administrador.

### ControllerTenant

- **index()** — `GET /api/tenants` — Lista todos los tenants con `dias_restantes`, `estado`, `esta_vencido`, `puede_acceder`.
- **show($id)** — `GET /api/tenants/{id}` — Devuelve un tenant por ID.
- **store(Request)** — `POST /api/tenants` — Crea tenant (validación incluida).
- **update(Request, $id)** — `PUT` o `POST /api/tenants/{id}` — Actualiza tenant.
- **destroy($id)** — `DELETE /api/tenants/{id}` — Elimina tenant.
- **verificarEstado($subdominio)** — `GET /api/tenants/verificar/{subdominio}` — Estado y si puede acceder.

---

## 4. Middleware

| Middleware   | Archivo                          | Registro en Kernel      | Uso                                                                 |
|-------------|----------------------------------|-------------------------|---------------------------------------------------------------------|
| `AdminAuth`| `app/Http/Middleware/AdminAuth.php` | `'admin' => AdminAuth::class` | Protege rutas de administración de tenants; exige JWT con `roll = 'admin'`. |

### AdminAuth (flujo)

1. Lee `Authorization: Bearer <token>`.
2. Decodifica JWT con `FIRMA_TOKEN`.
3. Comprueba que `roll === 'admin'`.
4. Carga `AdminUser` por `id` del token y comprueba que esté `activo`.
5. Guarda en `$request->attributes`: `admin_user`, `admin_jwt`.

Si falla token o rol: 401/403 JSON.

---

## 5. Rutas (`routes/web.php`)

Todas están **fuera** del grupo `Route::middleware(['tenant'])`, para usar siempre la BD maestra.

### Interfaz (HTML)

```text
GET /admin-tenants   →  view('admin_tenants')
```

No usa middleware `admin`; la página hace login vía API y luego llama a la API con el token.

### API administrador (pública)

```text
POST /api/admin/login   →  ControllerAdminAuth@login
```

### API administración de tenants (protegida con `admin`)

```text
GET    /api/admin/me                    →  ControllerAdminAuth@me
GET    /api/tenants                     →  ControllerTenant@index
GET    /api/tenants/verificar/{subdominio}  →  ControllerTenant@verificarEstado
GET    /api/tenants/{id}                →  ControllerTenant@show
POST   /api/tenants                     →  ControllerTenant@store
PUT    /api/tenants/{id}                →  ControllerTenant@update
POST   /api/tenants/{id}                →  ControllerTenant@update
DELETE /api/tenants/{id}                →  ControllerTenant@destroy
```

El grupo completo está definido como:

```php
Route::middleware(['admin'])->group(function () {
    // ... rutas anteriores
});
```

---

## 6. Vistas (Blade)

| Vista               | Archivo                                | Descripción                                                                 |
|---------------------|----------------------------------------|-----------------------------------------------------------------------------|
| Panel admin tenants | `resources/views/admin_tenants.blade.php` | Una sola página: formulario de login + listado de tenants + modal crear/editar. HTML + Bootstrap 5 + JS vanilla; token en `localStorage`. |
| Tenant bloqueado    | `resources/views/tenant_bloqueado.blade.php` | Página mostrada por `TenantMiddleware` cuando el tenant no puede acceder (bloqueado, inactivo o vencido). No es parte del “administrador” pero usa datos de `tenants`. |

---

## 7. Seeders

| Seeder           | Archivo                                  | Uso                                                                 |
|-----------------|-------------------------------------------|---------------------------------------------------------------------|
| AdminUserSeeder | `database/seeds/AdminUserSeeder.php`      | Crea un administrador por defecto: usuario `admin`, contraseña `admin123`. |

Ejecución:

```bash
php artisan db:seed --class=AdminUserSeeder
```

No crea usuarios en la tabla `usuarios` de los tenants; solo en `admin_users` (BD maestra).

---

## 8. Migraciones

| Migración | Archivo                                                                 | Crea / modifica   |
|-----------|-------------------------------------------------------------------------|-------------------|
| Admin users | `database/migrations/2026_03_04_100000_create_admin_users_table.php` | Tabla `admin_users` (conexión `mysql`) |
| Tenants   | `database/migrations/2026_01_16_000000_create_tenants_table.php`     | Tabla `tenants`   |

Ejecutar migración de administradores:

```bash
php artisan migrate --path=database/migrations/2026_03_04_100000_create_admin_users_table.php
```

---

## 9. Resumen de archivos por tipo

```
kadoshbackend/
├── app/
│   ├── AdminUser.php                          # Modelo administrador
│   ├── Tenant.php                             # Modelo tenant
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ControllerAdminAuth.php        # Login y /api/admin/me
│   │   │   └── ControllerTenant.php           # CRUD y verificar estado
│   │   └── Middleware/
│   │       ├── AdminAuth.php                  # Protección JWT admin
│   │       └── TenantMiddleware.php           # Selección BD por subdominio (no solo admin)
├── database/
│   ├── migrations/
│   │   ├── 2026_03_04_100000_create_admin_users_table.php
│   │   └── 2026_01_16_000000_create_tenants_table.php
│   └── seeds/
│       └── AdminUserSeeder.php
├── resources/
│   └── views/
│       ├── admin_tenants.blade.php            # Interfaz del administrador
│       └── tenant_bloqueado.blade.php        # Página “tenant bloqueado”
├── routes/
│   └── web.php                               # Rutas /admin-tenants y /api/admin/*, /api/tenants/*
└── docs/
    └── ADMINISTRADOR_TENANTS_REFERENCIA.md    # Este documento
```

---

## 10. Cómo acceder al panel

1. **URL:** `https://<dominio>/admin-tenants` (sin subdominio; ej. `http://localhost:8000/admin-tenants`).
2. **Primera vez:** ejecutar migración de `admin_users` y `AdminUserSeeder` (ver secciones 7 y 8).
3. **Login:** usuario y contraseña de un registro en `admin_users` (por defecto `admin` / `admin123`).
4. Las peticiones a la API de tenants llevan en cabecera: `Authorization: Bearer <token>` (token devuelto por `POST /api/admin/login`).

---

## 11. Variables de entorno

- **FIRMA_TOKEN:** usada para firmar y verificar el JWT del administrador (misma que para el resto de la app).
- **DB_*** (conexión `mysql`): base de datos maestra donde están `admin_users` y `tenants`.

---

*Documento de referencia técnica del administrador de tenants. Para uso funcional del módulo ver `MODULO_ADMINISTRAR_TENANTS.md`.*
