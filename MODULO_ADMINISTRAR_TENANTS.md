# 🏢 Módulo de Administración de Tenants - Documentación

## 📋 Descripción General

El módulo de **Administración de Tenants** permite gestionar todos los clientes (tenants) del sistema multi-tenant de Kadosh. Desde este módulo puedes crear, editar, eliminar y monitorear el estado de cada tenant, incluyendo sus fechas de vencimiento, estado de acceso y información de contacto.

---

## 🚀 Acceso al Módulo

### Desde el Menú Principal

1. Inicia sesión en el sistema
2. En el menú lateral, busca la opción **"Administrar Tenants"**
3. Haz clic en el enlace o icono

**Ruta**: `/administrar_tenants`

**Icono**: `<i className="fas fa-building"></i>`

---

## ✨ Funcionalidades Principales

### 1. **Listar Tenants**
- Visualiza todos los tenants registrados en el sistema
- Muestra información clave: nombre, subdominio, base de datos, estado, fecha de vencimiento
- Ordenados por fecha de creación (más recientes primero)

### 2. **Crear Nuevo Tenant**
- Formulario completo para registrar un nuevo tenant
- Validación de campos requeridos
- Verificación de subdominio único

### 3. **Editar Tenant**
- Modificar información de un tenant existente
- Actualizar fecha de vencimiento
- Activar/desactivar o bloquear/desbloquear

### 4. **Eliminar Tenant**
- Eliminar un tenant del sistema (con confirmación)
- ⚠️ **Nota**: Esto solo elimina el registro, no la base de datos

### 5. **Filtrar y Buscar**
- Filtrar por estado (activo, vencido, bloqueado, etc.)
- Buscar por nombre, subdominio, contacto

### 6. **Monitoreo de Estado**
- Visualización de días restantes hasta vencimiento
- Indicadores de estado con colores
- Alertas visuales para tenants próximos a vencer

---

## 📝 Campos del Formulario

### Campos Requeridos

| Campo | Tipo | Descripción | Ejemplo |
|-------|------|-------------|---------|
| **Nombre** | Texto | Nombre descriptivo del tenant | "Clínica ABC" |
| **Subdominio** | Texto | Subdominio único (sin espacios ni caracteres especiales) | "clinica1" |
| **Base de Datos** | Texto | Nombre de la base de datos del tenant | "kadosh_clinica1" |

### Campos Opcionales

| Campo | Tipo | Descripción | Ejemplo |
|-------|------|-------------|---------|
| **Fecha de Vencimiento** | Fecha | Fecha límite de la licencia | "2026-12-31" |
| **Activo** | Checkbox | Si el tenant está activo (por defecto: sí) | ☑️ |
| **Bloqueado** | Checkbox | Si el tenant está bloqueado (por defecto: no) | ☐ |
| **Nombre de Contacto** | Texto | Nombre de la persona de contacto | "Juan Pérez" |
| **Email de Contacto** | Email | Correo electrónico de contacto | "contacto@clinica.com" |
| **Teléfono de Contacto** | Texto | Número de teléfono | "809-555-1234" |
| **Notas** | Texto largo | Notas adicionales sobre el tenant | "Cliente premium, renovar en enero" |

---

## 🎨 Estados de los Tenants

El sistema calcula automáticamente el estado de cada tenant basándose en sus propiedades:

### Estados Posibles

| Estado | Descripción | Color | Condición |
|--------|-------------|-------|-----------|
| **Activo** | Tenant funcionando normalmente | Verde | `activo = true`, `bloqueado = false`, no vencido |
| **Vencido** | La licencia ha expirado | Rojo | `fecha_vencimiento < hoy` |
| **Por Vencer** | Vence en 7 días o menos | Amarillo | `dias_restantes <= 7` |
| **Bloqueado** | Tenant bloqueado manualmente | Gris oscuro | `bloqueado = true` |
| **Inactivo** | Tenant desactivado | Gris | `activo = false` |

### Cálculo de Días Restantes

- **Positivo**: Faltan X días para vencer
- **Cero**: Vence hoy
- **Negativo**: Vencido hace X días
- **Null**: No tiene fecha de vencimiento

---

## 🔍 Filtros y Búsqueda

### Filtro por Estado

Puedes filtrar los tenants por su estado actual:

- **Todos**: Muestra todos los tenants
- **Activo**: Solo tenants activos
- **Por Vencer**: Tenants que vencen en 7 días o menos
- **Vencido**: Tenants con licencia expirada
- **Bloqueado**: Tenants bloqueados
- **Inactivo**: Tenants desactivados

### Búsqueda de Texto

El campo de búsqueda busca en:
- Nombre del tenant
- Subdominio
- Nombre de contacto
- Email de contacto
- Teléfono de contacto

**Ejemplo**: Buscar "ABC" mostrará todos los tenants que contengan "ABC" en cualquiera de estos campos.

---

## 📊 Interfaz de Usuario

### Vista de Lista (Cards)

Cada tenant se muestra en una tarjeta con:

- **Header**: Nombre del tenant + Badge de estado
- **Información**:
  - 🌐 Subdominio
  - 💾 Base de datos
  - 📅 Fecha de vencimiento (si existe)
  - ⏰ Días restantes (si aplica)
- **Acciones**: Botones "Editar" y "Eliminar"

### Modal de Crear/Editar

Modal moderno con:
- Header con gradiente azul
- Formulario completo con todos los campos
- Validación en tiempo real
- Botones de acción estilizados

---

## 🔌 API Endpoints

### Base URL
```
/api/tenants
```

### Endpoints Disponibles

#### 1. Listar Todos los Tenants
```http
GET /api/tenants
```

**Respuesta exitosa (200)**:
```json
[
  {
    "id": 1,
    "nombre": "Clínica ABC",
    "subdominio": "clinica1",
    "database_name": "kadosh_clinica1",
    "fecha_vencimiento": "2026-12-31",
    "activo": true,
    "bloqueado": false,
    "dias_restantes": 350,
    "estado": "activo",
    "esta_vencido": false,
    "puede_acceder": true,
    "contacto_nombre": "Juan Pérez",
    "contacto_email": "contacto@clinica.com",
    "contacto_telefono": "809-555-1234",
    "notas": "Cliente premium",
    "created_at": "2026-01-16T10:00:00.000000Z",
    "updated_at": "2026-01-16T10:00:00.000000Z"
  }
]
```

#### 2. Crear Tenant
```http
POST /api/tenants
Content-Type: application/json
```

**Body**:
```json
{
  "nombre": "Clínica XYZ",
  "subdominio": "clinica2",
  "database_name": "kadosh_clinica2",
  "fecha_vencimiento": "2026-12-31",
  "activo": true,
  "bloqueado": false,
  "contacto_nombre": "María García",
  "contacto_email": "maria@clinica.com",
  "contacto_telefono": "809-555-5678",
  "notas": "Nuevo cliente"
}
```

**Respuesta exitosa (201)**:
```json
{
  "message": "Tenant creado exitosamente",
  "tenant": { ... }
}
```

**Errores posibles**:
- `422`: Error de validación
- `500`: Error del servidor

#### 3. Actualizar Tenant
```http
PUT /api/tenants/{id}
Content-Type: application/json
```

**Body**: Mismos campos que crear (todos opcionales excepto los requeridos)

**Respuesta exitosa (200)**:
```json
{
  "message": "Tenant actualizado exitosamente",
  "tenant": { ... }
}
```

#### 4. Eliminar Tenant
```http
DELETE /api/tenants/{id}
```

**Respuesta exitosa (200)**:
```json
{
  "message": "Tenant eliminado exitosamente"
}
```

#### 5. Obtener Tenant Específico
```http
GET /api/tenants/{id}
```

**Respuesta exitosa (200)**:
```json
{
  "id": 1,
  "nombre": "Clínica ABC",
  ...
}
```

#### 6. Verificar Estado por Subdominio
```http
GET /api/tenants/verificar/{subdominio}
```

**Ejemplo**: `GET /api/tenants/verificar/clinica1`

**Respuesta exitosa (200)**:
```json
{
  "tenant": { ... },
  "puede_acceder": true,
  "esta_vencido": false,
  "dias_restantes": 350,
  "estado": "activo"
}
```

---

## 📖 Ejemplos de Uso

### Ejemplo 1: Crear un Nuevo Tenant

1. Haz clic en el botón **"Nuevo Tenant"** (arriba a la derecha)
2. Completa el formulario:
   - **Nombre**: "Clínica San José"
   - **Subdominio**: "sanjose"
   - **Base de Datos**: "kadosh_sanjose"
   - **Fecha de Vencimiento**: "2026-12-31"
   - **Contacto**: Completa los datos de contacto
3. Haz clic en **"Guardar"**
4. El sistema creará el registro en la tabla `tenants`

⚠️ **Importante**: Después de crear el tenant, debes:
- Crear la base de datos físicamente
- Aplicar las migraciones a esa base de datos

### Ejemplo 2: Extender la Licencia de un Tenant

1. Busca el tenant en la lista
2. Haz clic en **"Editar"**
3. Actualiza la **Fecha de Vencimiento**
4. Haz clic en **"Actualizar"**

### Ejemplo 3: Bloquear un Tenant Temporalmente

1. Busca el tenant
2. Haz clic en **"Editar"**
3. Marca la casilla **"Bloqueado"**
4. Guarda los cambios
5. El tenant no podrá acceder al sistema hasta que se desbloquee

### Ejemplo 4: Filtrar Tenants por Vencer

1. En el filtro de estado, selecciona **"Por Vencer"**
2. Verás solo los tenants que vencen en 7 días o menos
3. Útil para hacer seguimiento de renovaciones

---

## ⚠️ Validaciones

### Validaciones del Backend

| Campo | Reglas |
|-------|--------|
| `nombre` | Requerido, máximo 255 caracteres |
| `subdominio` | Requerido, máximo 100 caracteres, único |
| `database_name` | Requerido, máximo 255 caracteres |
| `fecha_vencimiento` | Opcional, debe ser una fecha válida |
| `contacto_email` | Opcional, debe ser un email válido |
| `activo` | Opcional, boolean |
| `bloqueado` | Opcional, boolean |

### Errores Comunes

1. **"El subdominio ya está en uso"**
   - Solución: Elige otro subdominio único

2. **"Error al crear tenant"**
   - Verifica que la base de datos maestra esté accesible
   - Revisa los logs del servidor

3. **"Error de validación"**
   - Revisa que todos los campos requeridos estén completos
   - Verifica el formato del email si lo ingresaste

---

## 🔧 Configuración Técnica

### Base de Datos

La tabla `tenants` debe estar en la **base de datos maestra** (no en las bases de datos de los tenants).

**Conexión**: El modelo `Tenant` usa la conexión `mysql` (base de datos maestra).

### Middleware

El `TenantMiddleware` consulta esta tabla para:
1. Verificar que el tenant existe
2. Obtener el `database_name` correspondiente
3. Verificar permisos de acceso
4. Configurar la conexión dinámica

---

## 🛠️ Troubleshooting

### Problema: No se cargan los tenants

**Solución**:
1. Verifica que la tabla `tenants` exista en la base de datos maestra
2. Revisa la conexión a la base de datos en `.env`
3. Verifica los logs: `storage/logs/laravel.log`

### Problema: Error al crear tenant

**Solución**:
1. Verifica que el subdominio sea único
2. Asegúrate de que todos los campos requeridos estén completos
3. Revisa que la base de datos maestra esté accesible

### Problema: El estado no se actualiza correctamente

**Solución**:
1. Verifica que la fecha de vencimiento esté en formato correcto (YYYY-MM-DD)
2. Asegúrate de que los campos `activo` y `bloqueado` sean booleanos
3. Recarga la página para ver los cambios

### Problema: No puedo acceder al módulo

**Solución**:
1. Verifica que la ruta esté registrada en `routes/web.php`
2. Asegúrate de estar autenticado
3. Verifica permisos de usuario (si aplica)

---

## 📋 Checklist de Creación de Tenant

Cuando crees un nuevo tenant, asegúrate de:

- [ ] Crear el registro en la tabla `tenants` (desde el módulo)
- [ ] Crear la base de datos físicamente en MySQL
- [ ] Aplicar las migraciones a la nueva base de datos
- [ ] Configurar el subdominio en el servidor web (DNS/Virtual Host)
- [ ] Verificar que el tenant pueda acceder con su subdominio
- [ ] Configurar permisos de usuario de base de datos (si aplica)
- [ ] Hacer un backup inicial de la base de datos

---

## 🎯 Mejores Prácticas

1. **Nomenclatura de Subdominios**
   - Usa nombres descriptivos pero cortos
   - Evita caracteres especiales
   - Ejemplo: `clinica1`, `sanjose`, `centro`

2. **Nomenclatura de Bases de Datos**
   - Usa un prefijo consistente (ej: `kadosh_`)
   - Sigue el mismo patrón que el subdominio
   - Ejemplo: `kadosh_clinica1`

3. **Fechas de Vencimiento**
   - Establece fechas realistas
   - Revisa regularmente los tenants por vencer
   - Usa el filtro "Por Vencer" semanalmente

4. **Información de Contacto**
   - Completa siempre los datos de contacto
   - Facilita la comunicación para renovaciones
   - Actualiza si cambian

5. **Notas**
   - Usa el campo de notas para información importante
   - Ejemplos: "Renovar en enero", "Cliente premium", "Pago pendiente"

6. **Backups**
   - Haz backup antes de modificar un tenant
   - Documenta cambios importantes

---

## 📞 Soporte

Si encuentras problemas:

1. Revisa los logs: `storage/logs/laravel.log`
2. Verifica la configuración de la base de datos
3. Consulta `ARQUITECTURA_MULTI_TENANT.md` para entender la arquitectura
4. Contacto: edisondja@gmail.com

---

## 🔄 Relación con Otros Módulos

### Comando Automático de Verificación

El sistema incluye un comando que verifica automáticamente los vencimientos:

```bash
php artisan tenants:verificar-vencimientos
```

Este comando:
- Se ejecuta cada hora (configurado en `app/Console/Kernel.php`)
- Bloquea automáticamente tenants vencidos
- Desactiva tenants vencidos

### Middleware de Acceso

El `TenantMiddleware` usa la información de este módulo para:
- Verificar acceso al sistema
- Configurar la conexión de base de datos
- Mostrar mensajes de bloqueo/vencimiento

---

**¡Módulo de Administración de Tenants listo para usar! 🚀**
