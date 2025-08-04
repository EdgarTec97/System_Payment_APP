# E-Commerce Laravel Application

## Descripción del Proyecto

Esta es una aplicación de comercio electrónico completa desarrollada con Laravel 10, que incluye autenticación avanzada, sistema de roles, gestión de productos y órdenes, integración con Stripe para pagos, sistema de eventos con Kafka, caché estratégico con Redis, y un frontend responsive con Bootstrap y Tailwind CSS.

## Características Principales

### 🔐 Sistema de Autenticación y Autorización
- **Autenticación completa**: Registro, login, logout, verificación por email
- **Sistema de roles**: ADMIN, SUPPORT, BASIC con permisos granulares
- **Verificación por email**: Integración con AWS SES y eventos Kafka
- **Middlewares de seguridad**: Rate limiting, headers de seguridad, validación CSRF

### 🛍️ Gestión de Productos
- **CRUD completo** por roles (SUPPORT y ADMIN)
- **Imágenes múltiples**: Hasta 3 imágenes por producto con AWS S3
- **Control de stock**: SELECT FOR UPDATE para prevenir race conditions
- **Filtros avanzados**: Por precio, categoría, stock, búsqueda de texto
- **Caché inteligente**: Redis con invalidación automática

### 📦 Sistema de Órdenes
- **Estados de orden**: draft, created, paid, cancelled, delivered
- **Carrito de compras**: Funcionalidad completa con validación de stock
- **Integración Stripe**: PaymentIntents, webhooks, manejo de errores
- **Facturación**: Generación de PDFs con datos completos
- **Auditoría**: Historial completo de cambios

### 💳 Pagos con Stripe
- **PaymentIntents**: Implementación profesional con 3D Secure
- **Webhooks**: Manejo completo de eventos de Stripe
- **Seguridad**: Validación de firmas y manejo de errores
- **Reintento automático**: Para pagos fallidos

### 📊 Sistema de Caché (Redis)
- **Caché estratégico**: Productos, órdenes, estadísticas
- **Invalidación automática**: Observers que limpian caché al actualizar datos
- **TTL diferenciado**: Según tipo de dato y frecuencia de cambio
- **Comandos Artisan**: Para gestión manual del caché

### 🔍 Sistema de Auditoría
- **Logging completo**: Todas las requests con métricas de rendimiento
- **Middleware de auditoría**: Captura automática de actividad
- **Base de datos**: Almacenamiento selectivo de eventos importantes
- **Métricas**: Tiempo de ejecución, uso de memoria, tamaño de respuesta

### 🎨 Frontend Responsive
- **Bootstrap 5 + Tailwind CSS**: Combinación perfecta para diseño moderno
- **SweetAlert2**: Alertas y confirmaciones elegantes
- **JavaScript funcional**: Carrito dinámico, validación en tiempo real
- **Mobile-first**: Diseño completamente responsive

### 📚 API RESTful
- **Documentación Swagger**: API completamente documentada
- **Rate limiting**: Múltiples niveles según contexto
- **Autenticación JWT**: Laravel Sanctum
- **Respuestas consistentes**: Formato estándar para todas las respuestas

## Tecnologías Utilizadas

### Backend
- **Laravel 10**: Framework PHP moderno
- **PostgreSQL**: Base de datos relacional robusta
- **Redis**: Caché y sesiones
- **Laravel Sanctum**: Autenticación API
- **Spatie Activity Log**: Auditoría de modelos

### Integraciones
- **Stripe**: Procesamiento de pagos
- **AWS SES**: Envío de emails
- **AWS S3**: Almacenamiento de imágenes
- **Kafka**: Sistema de eventos (simulado con Laravel Events)

### Frontend
- **Bootstrap 5**: Framework CSS
- **Tailwind CSS**: Utilidades CSS
- **SweetAlert2**: Alertas modernas
- **Vanilla JavaScript**: Funcionalidad interactiva

### Herramientas de Desarrollo
- **Swagger/OpenAPI**: Documentación de API
- **PHPUnit**: Testing automatizado
- **Laravel Telescope**: Debugging y monitoreo
- **Composer**: Gestión de dependencias PHP
- **NPM**: Gestión de dependencias JavaScript

## Instalación y Configuración

### Requisitos Previos
- PHP 8.1 o superior
- Composer
- Node.js y NPM
- PostgreSQL
- Redis
- Git

### Pasos de Instalación

1. **Clonar el repositorio**
```bash
git clone <repository-url>
cd ecommerce-app
```

2. **Instalar dependencias PHP**
```bash
composer install
```

3. **Instalar dependencias JavaScript**
```bash
npm install
```

4. **Configurar variables de entorno**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Configurar base de datos**
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ecommerce
DB_USERNAME=postgres
DB_PASSWORD=password
```

6. **Configurar Redis**
```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

7. **Configurar Stripe**
```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

8. **Configurar AWS**
```env
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_bucket_name
AWS_SES_REGION=us-east-1
```

9. **Ejecutar migraciones y seeders**
```bash
php artisan migrate --seed
```

10. **Compilar assets**
```bash
npm run build
```

11. **Iniciar servidor**
```bash
php artisan serve
```

## Estructura del Proyecto

```
ecommerce-app/
├── app/
│   ├── Console/Commands/          # Comandos Artisan personalizados
│   ├── Events/                    # Eventos del sistema
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/              # Controladores API
│   │   │   ├── Auth/             # Controladores de autenticación
│   │   │   ├── Admin/            # Controladores de administración
│   │   │   └── Support/          # Controladores de soporte
│   │   └── Middleware/           # Middlewares personalizados
│   ├── Listeners/                # Listeners de eventos
│   ├── Mail/                     # Clases de email
│   ├── Models/                   # Modelos Eloquent
│   ├── Observers/                # Observers de modelos
│   └── Services/                 # Servicios de negocio
├── database/
│   ├── migrations/               # Migraciones de base de datos
│   └── seeders/                  # Seeders de datos
├── resources/
│   ├── css/                      # Estilos CSS
│   ├── js/                       # JavaScript
│   └── views/                    # Vistas Blade
├── routes/
│   ├── api.php                   # Rutas API
│   └── web.php                   # Rutas web
└── tests/                        # Tests automatizados
```

## API Documentation

La documentación completa de la API está disponible en `/api/documentation` cuando la aplicación está ejecutándose.

### Endpoints Principales

#### Autenticación
- `POST /api/auth/register` - Registro de usuario
- `POST /api/auth/login` - Inicio de sesión
- `POST /api/auth/logout` - Cerrar sesión
- `GET /api/auth/user` - Obtener usuario autenticado

#### Productos
- `GET /api/products` - Listar productos con filtros
- `GET /api/products/{id}` - Obtener producto específico
- `GET /api/products/featured` - Productos destacados
- `GET /api/products/search` - Búsqueda de productos

#### Órdenes
- `GET /api/orders` - Listar órdenes del usuario
- `POST /api/orders` - Crear nueva orden
- `GET /api/orders/{id}` - Obtener orden específica
- `POST /api/orders/{id}/cancel` - Cancelar orden

#### Carrito
- `GET /api/cart` - Obtener carrito actual
- `POST /api/cart/add/{productId}` - Agregar producto al carrito
- `PUT /api/cart/update/{itemId}` - Actualizar cantidad
- `DELETE /api/cart/remove/{itemId}` - Remover producto

## Testing

### Ejecutar Tests
```bash
# Todos los tests
php artisan test

# Tests específicos
php artisan test --filter AuthTest
php artisan test --filter ProductTest
php artisan test --filter OrderTest
```

### Cobertura de Tests
- **Autenticación**: Registro, login, logout, cambio de contraseña
- **Productos**: CRUD, filtros, búsqueda, caché
- **Órdenes**: Creación, actualización, cancelación, pagos
- **API**: Rate limiting, validación, respuestas

## Comandos Artisan Personalizados

### Gestión de Caché
```bash
# Limpiar caché de productos
php artisan cache:clear-products

# Limpiar caché de órdenes
php artisan cache:clear-orders

# Calentar caché
php artisan cache:warm-up
```

### Generación de Documentación
```bash
# Generar documentación Swagger
php artisan l5-swagger:generate
```

## Seguridad

### Medidas Implementadas
- **CSRF Protection**: Tokens en todos los formularios
- **XSS Protection**: Escape automático en vistas
- **SQL Injection**: Uso de Eloquent ORM y prepared statements
- **Rate Limiting**: Múltiples niveles según contexto
- **Headers de Seguridad**: CSP, HSTS, X-Frame-Options
- **Validación de Entrada**: Sanitización de todos los inputs
- **Autenticación Robusta**: Verificación por email, roles y permisos

### Configuración de Seguridad
```env
# Headers de seguridad
SECURITY_HEADERS_ENABLED=true
CSP_ENABLED=true

# Rate limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_MAX_ATTEMPTS=60
```

## Monitoreo y Logging

### Logs Disponibles
- **Aplicación**: `storage/logs/laravel.log`
- **Auditoría**: Base de datos `audit_logs`
- **Requests**: Middleware de auditoría
- **Errores**: Logging automático con contexto

### Métricas
- Tiempo de respuesta de requests
- Uso de memoria por request
- Estadísticas de caché (hits/misses)
- Errores de pago y reintentos

## Deployment

### Preparación para Producción
```bash
# Optimizar autoloader
composer install --optimize-autoloader --no-dev

# Cachear configuración
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Compilar assets para producción
npm run build
```

### Variables de Entorno de Producción
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Base de datos de producción
DB_CONNECTION=pgsql
DB_HOST=your_production_host

# Redis de producción
REDIS_HOST=your_redis_host

# Configuración de email
MAIL_MAILER=ses
AWS_SES_REGION=us-east-1
```

## Contribución

### Estándares de Código
- **PSR-12**: Estándar de codificación PHP
- **Laravel Best Practices**: Convenciones del framework
- **SOLID Principles**: Principios de diseño orientado a objetos
- **Clean Code**: Código limpio y mantenible

### Proceso de Contribución
1. Fork del repositorio
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit de cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## Licencia

Este proyecto está licenciado bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## Soporte

Para soporte técnico o preguntas sobre el proyecto:
- **Email**: admin@ecommerce.com
- **Documentación**: `/api/documentation`
- **Issues**: GitHub Issues

## Changelog

### v1.0.0 (2025-01-01)
- ✅ Sistema de autenticación completo
- ✅ Gestión de productos con imágenes
- ✅ Sistema de órdenes y carrito
- ✅ Integración con Stripe
- ✅ Caché estratégico con Redis
- ✅ API RESTful documentada
- ✅ Frontend responsive
- ✅ Sistema de auditoría
- ✅ Tests automatizados

