# Changelog - WhatsCommerce

## [1.7.8] - 2025-01-22

### Agregado
- Sistema de pruebas automáticas para verificar dependencias
- Verificación de archivos y clases requeridas
- Verificación de configuración del plugin
- Mensajes de error detallados en el panel de administración

### Mejorado
- Sistema de inicialización del plugin
- Manejo de errores y logging
- Carga de archivos y dependencias

## [1.7.7] - 2025-01-22

### Corregido
- Error al intentar crear instancia de WhatsCommerce desde ámbito global
- Mejorada la inicialización de la clase principal
- Optimizado el manejo de errores durante la inicialización

## [1.7.6] - 2025-01-22

### Corregido
- Error de clase WhatsCommerce no encontrada
- Agregado namespace WhatsCommerce a la clase principal

## [1.7.5] - 2025-01-22

### Corregido
- Error de carga de clases usando namespaces
- Problema con la carga temprana de traducciones
- Error en la inicialización del servicio de Twilio

### Mejorado
- Implementado sistema de autoload con namespaces
- Optimizada la carga de archivos de clase
- Mejorado el manejo de errores en la inicialización del plugin
- Actualizada la documentación de la clase TwilioService

## [1.7.4] - 2025-01-22

### Cambiado
- Actualizada la documentación del plugin con información más detallada
- Mejorada la documentación de todas las clases principales
- Actualizado el correo de soporte a soporte@aplicacionesweb.cl

### Agregado
- Nueva estructura de mensajes predefinidos en MessageManager
- Documentación completa de PHPDoc en todas las clases
- Integración con sistema de logging mejorado
- Repositorio público en GitHub

### Optimizado
- Simplificado el sistema de almacenamiento de usuarios usando metadatos de WordPress
- Mejorado el manejo de errores y logging en todas las clases

## [1.7.3] - 2025-01-15

### Agregado
- Nuevo sistema de logging usando Monolog
- Mejoras en el manejo de conversaciones
- Nuevas plantillas de mensajes personalizables

### Corregido
- Error en la creación de pedidos cuando el carrito está vacío
- Problema con la validación de números de teléfono internacionales
- Bug en la actualización del estado de las conversaciones

## [1.7.2] - 2025-01-08

### Agregado
- Soporte para múltiples idiomas (ES, EN)
- Nueva funcionalidad de repetir último pedido
- Panel de administración mejorado

### Cambiado
- Actualizada la integración con la API de Twilio
- Mejorado el sistema de búsqueda de productos
- Optimizado el rendimiento general del plugin

### Corregido
- Problemas de compatibilidad con WooCommerce 8.0
- Error en el proceso de checkout
- Bug en la sincronización de estados de pedido

## [1.6] - 1985
### Added
- Verificación de dependencias para WooCommerce y Transbank.
- Sanitización y modularización en configuración de API y mensajes.
- Carga condicional de estilos en la página de ajustes de WhatsCommerce.

### Improved
- Código optimizado para uso de constantes y seguridad de claves API.
- Traducción más robusta utilizando `load_plugin_textdomain`.
- Mejoras de rendimiento al limitar carga de scripts a páginas necesarias.

## [1.4] - 2024-11-01
### Added
- Integración con Twilio para enviar mensajes de WhatsApp.
- Configuración de número de WhatsApp de Twilio desde la página de ajustes.
- Mejoras de diseño en la página de ajustes del plugin.

### Fixed
- Corrección de enlaces en la descripción de la clave API de WooCommerce.

## [1.3] - 2024-10-15
### Added
- Soporte de internacionalización para español (es_ES, es_CL) e inglés (en_US).

## [1.2] - 2024-10-01
### Added
- Menú de configuración en WooCommerce para integrar con WhatsApp.
- Funcionalidad de verificación de clientes registrados.
- Enlace para acceder directamente a la configuración desde el listado de plugins.

## [1.0] - 2024-09-15
### Added
- Versión inicial del plugin.
- Comunicación básica con WhatsApp para gestionar pedidos.
