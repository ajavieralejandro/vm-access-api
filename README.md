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
