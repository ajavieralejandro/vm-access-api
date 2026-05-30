# VM Access API

Microservicio Laravel del ecosistema Villa Mitre / Surtek orientado a centralizar control de accesos, validaciones y trazabilidad en fases progresivas.

## Objetivo

Proveer una base tecnica segura y escalable para un servicio especializado en:

- validaciones de acceso para actividades,
- coordinacion entre microservicios internos,
- trazabilidad de eventos de ingreso,
- integracion futura con scanners y apps de control.

## Alcance actual (fase 1)

- Estructura base del microservicio.
- Healthcheck publico.
- Healthcheck interno protegido por header.
- Endpoint interno placeholder para validaciones futuras.
- Middleware de autorizacion interna por API key.
- Configuracion centralizada de integraciones.
- Helper de respuestas JSON consistentes.
- Logging minimo de intentos internos (sin secretos).
- Tests feature basicos.

No se implementa logica de negocio de acceso, QR, creditos, piletas, gimnasio ni proveedores externos.

## Variables de entorno

Definir en `.env` (base disponible en `.env.example`):

```env
APP_NAME="VM Access API"

INTERNAL_API_KEY=

VMSERVER_BASE_URL=
VMSERVER_INTERNAL_TOKEN=

VM_CREDITOS_API_BASE_URL=
VM_CREDITOS_INTERNAL_TOKEN=

VM_PILETAS_API_BASE_URL=
VM_PILETAS_INTERNAL_TOKEN=

VM_GYM_API_BASE_URL=
VM_GYM_INTERNAL_TOKEN=
```

Configuracion extendida en `config/integrations.php`:

- base URLs,
- tokens internos,
- timeouts,
- flags de habilitacion futura.

## Rutas disponibles

### Publica

- `GET /api/health`

Respuesta:

```json
{
	"ok": true,
	"service": "vm-access-api",
	"status": "healthy"
}
```

### Internas (requieren `X-Internal-Key`)

- `GET /api/internal/health`
- `POST /api/internal/access/validate`

Respuesta de placeholder:

```json
{
	"ok": false,
	"status": "not_implemented",
	"message": "Access validation flow is not implemented yet."
}
```

## Ejemplos curl

```bash
curl -i https://access-api.dominio.com/api/health
```

```bash
curl -i \
	-H "Accept: application/json" \
	-H "X-Internal-Key: TU_INTERNAL_KEY" \
	https://access-api.dominio.com/api/internal/health
```

```bash
curl -i \
	-X POST \
	-H "Accept: application/json" \
	-H "X-Internal-Key: TU_INTERNAL_KEY" \
	https://access-api.dominio.com/api/internal/access/validate
```

## Correr tests

```bash
php artisan test
```

Para correr solo los tests de esta fase:

```bash
php artisan test --filter=AccessApiTest
```

## Proximas fases sugeridas

- Definir contrato de validacion de acceso con request/response formal.
- Agregar DTOs, validaciones y manejo de errores por dominio.
- Implementar clientes HTTP reales para vmServer, creditos, piletas y gym.
- Incorporar trazabilidad persistente (access logs/event sourcing).
- Diseñar esquema de autorizacion interna con rotacion de credenciales.

---

## Fase 2: Access Zones, Access Passes y Access Logs

### Alcance

- Modelos y migraciones locales: `access_zones`, `access_passes`, `access_logs`.
- Creacion de pases de acceso via API interna protegida.
- Validacion local de pases por codigo y zona.
- Registro obligatorio de cada intento en `access_logs`.
- Reglas de rechazo: codigo inexistente, zona incorrecta/inactiva, pass vencido/revocado/usado/no vigente.
- Sin integracion real con otros microservicios.

### Migraciones

```bash
php artisan migrate
```

Tablas nuevas: `access_zones`, `access_passes`, `access_logs`.

### Seeder de zonas base

```bash
php artisan db:seed --class=AccessZoneSeeder
```

Crea o actualiza las zonas: `club`, `pileta`, `gym`.

### Endpoints nuevos

#### Crear access pass (interno protegido)

`POST /api/internal/access-passes` — requiere `X-Internal-Key`.

Payload:

```json
{
	"zone": "pileta",
	"vmserver_user_id": 123,
	"dni": "30111222",
	"holder_name": "Juan Pérez",
	"source_service": "piletas",
	"source_type": "inscription",
	"source_reference": "999",
	"valid_until": "2026-06-01T19:30:00-03:00"
}
```

Respuesta exitosa (201):

```json
{
	"ok": true,
	"access_pass": {
		"id": 1,
		"code": "AP_XXXXXXXXXXXX",
		"zone": "pileta",
		"status": "active"
	}
}
```

#### Validar acceso (publico versionado)

`POST /api/v1/access/validate` — no requiere autenticacion.

Respuesta si permite (200):

```json
{
	"ok": true,
	"allowed": true,
	"reason": "ok",
	"message": "Access allowed.",
	"access": { "zone": "pileta", "holder_name": "Juan Pérez", "direction": "in" }
}
```

Respuesta si rechaza (200):

```json
{
	"ok": true,
	"allowed": false,
	"reason": "expired_pass",
	"message": "Access denied."
}
```

### Ejemplos curl

```bash
curl -i -X POST http://127.0.0.1:8000/api/internal/access-passes \
	-H "Accept: application/json" \
	-H "Content-Type: application/json" \
	-H "X-Internal-Key: TU_INTERNAL_KEY" \
	-d '{"zone":"pileta","holder_name":"Juan Pérez","valid_until":"2026-06-01T19:30:00-03:00"}'
```

```bash
curl -i -X POST http://127.0.0.1:8000/api/v1/access/validate \
	-H "Accept: application/json" \
	-H "Content-Type: application/json" \
	-d '{"code":"CODIGO_GENERADO","zone":"pileta","direction":"in","scanner_device_id":"scanner-01"}'
```

### Decisiones pendientes (Fase 2)

- `POST /api/internal/access/validate` sigue como placeholder de Fase 1; decidir si redirigir al v1 o mantener contrato separado para escaners internos autenticados.
- No se marca el pass como `used` automaticamente; requiere decision de negocio (configurable en Fase 3).
- `valid_from` cuando no se envia se establece en `now()`; definir si ese comportamiento es definitivo.
- `access_logs.request_payload` almacena el payload de entrada sanitizado; definir politica de retencion de logs.

### Proximas fases sugeridas (desde Fase 2)

- Fase 3: Integrar `VmServerClient` real para validar identidad del titular del pass.
- Fase 4: Integrar `PiletasServiceClient` para confirmar inscripciones vigentes.
- Fase 5: Marcado automatico de pass como `used` segun tipo de actividad.
- Fase 6: Generacion y lectura de QR real (JWT firmado o UUID firmado).
- Fase 7: Dashboard de logs de acceso y alertas por patrones anomalos.
