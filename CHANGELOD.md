# Changelog
Todas las modificaciones notables de este proyecto se documentarán en este archivo.  
El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/)  
y este proyecto usa [Semantic Versioning](https://semver.org/lang/es/).

## [1.0.0] - 2025-08-22

### Añadido
- Primera versión estable del cliente **php-sharepoint-graph-api**.
- Soporte de autenticación con:
  - Client Secret
  - Certificado PEM
  - Certificado PFX
- Métodos para:
  - Obtener IDs de sitio y biblioteca.
  - Listar archivos y carpetas.
  - Subir archivos.
  - Eliminar archivos.
  - Verificar existencia de archivos y carpetas.
- Ejemplos prácticos incluidos en `/ejemplos`.

## [2.0.0] - 2025-08-26

### Cambiado
**BREAKING CHANGE**: la estructura del proyecto y la configuración han cambiado, 
requiriendo ajustes en la integración de los entornos que usaban la versión 1.x.

- Reestructuración global del proyecto para mejorar la organización del código.
- Refactorización profunda en módulos principales (configuración, autenticación, carga de certificados).
- Inclusión de nuevas funcionalidades en la gestión de entornos y soporte de certificados.
- Ajuste de rutas de certificados mediante variables de entorno (.env).
- Preparación para futuras extensiones y mantenimiento más sencillo.
