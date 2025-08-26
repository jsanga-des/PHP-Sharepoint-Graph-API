# TODO

## 1. Implementar el servicio de autenticación CRT (CertificateCrtAuth.php)
Actualmente la clase `CertificateCrtAuth` está vacía.  
**Pendiente:**  
- Inicializar propiedades como `clientId`, `tenantId`, rutas de certificado y clave.  
- Crear métodos para generar JWT y obtener el token de acceso.  
- Manejar expiración del token y limpieza de archivos temporales.

## 2. Añadir excepciones personalizadas en el cliente
Actualmente `SharepointClient` solo usa `SharepointException`.  
**Pendiente:**  
- Definir nuevas excepciones específicas para errores de CRT, JWT o token.  
- Integrarlas en el cliente para controlar mejor los errores (autenticación, servicios, etc).

## 3. Añadir nuevos métodos a FileService.php 
**Pendiente:**  
- Método para mover archivo de ubicación
- Renombrar archivos existentes

## 4. Añadir nuevo servicio para revisión de permisos
