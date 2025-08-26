<?php

// =============================================================================
// src/SharepointClient.php - Cliente principal (punto de entrada)
// =============================================================================

namespace SharepointClient;

use SharepointClient\Config\ConfigManager;
use SharepointClient\Authentication\AuthenticationManager;
use SharepointClient\Services\SiteService;
use SharepointClient\Services\DriveService;
use SharepointClient\Services\FileService;

/**
 * Cliente principal de Sharepoint
 *
 * Maneja la inicialización de servicios, autenticación y acceso
 * a sitios, bibliotecas y archivos en Sharepoint.
 */
class SharepointClient {
    private ConfigManager $config;
    private AuthenticationManager $authManager;
    private SiteService $siteService;
    private DriveService $driveService;
    private FileService $fileService;
    private string $siteId;
    private string $driveId;

    /**
     * Constructor
     *
     * @param ConfigManager $config Instancia de configuración para el cliente
     * @throws \Exception Si no se pueden inicializar los servicios
     */
    public function __construct(ConfigManager $config) {
        $this->config = $config;
        $this->authManager = new AuthenticationManager($this->config);
        $this->siteService = new SiteService($this->authManager);
        $this->driveService = new DriveService($this->authManager);
        $this->fileService = new FileService($this->authManager);

        try {
            $sitePath = $this->config->get('site_path');
            $driveName = $this->config->get('default_library', 'Documentos');

            $this->siteId = $this->siteService->getSiteId($sitePath);
            $this->driveId = $this->driveService->getDriveId($this->siteId, $driveName);

        } catch (\Exception $e) {
            throw new \Exception("Error al inicializar el cliente de Sharepoint: " . $e->getMessage());
        }
    }

    /**
     * Obtiene la instancia de configuración actual
     *
     * @return ConfigManager
     */
    public function getConfig(): ConfigManager {
        return $this->config;
    }

    /**
     * Obtiene el ID del sitio
     *
     * @return string
     */
    public function getSiteId(): string {
        return $this->siteId;
    }

    /**
     * Obtiene el ID de la biblioteca de documentos
     *
     * @return string
     */
    public function getDriveId(): string {
        return $this->driveId;
    }

    /**
     * Lista archivos y carpetas en una ruta remota
     *
     * @param string $remotePath Ruta de la carpeta remota
     * @param int $maxDepth Profundidad de la búsqueda
     * @return array
     */
    public function listFiles(string $remotePath = "", int $maxDepth = 1): array {
        return $this->fileService->listFiles($this->siteId, $this->driveId, $remotePath, $maxDepth);
    }

    /**
     * Sube un archivo a una ruta remota
     *
     * @param string $filePath Ruta del archivo local
     * @param string $remotePath Ruta de la carpeta de destino
     * @param string|null $fileName Nombre opcional para el archivo subido
     * @return bool
     */
    public function uploadFile(string $filePath, string $remotePath = '', ?string $fileName = null): bool {
        return $this->fileService->uploadFile($this->siteId, $this->driveId, $filePath, $remotePath, $fileName);
    }

    /**
     * Elimina un archivo de una ruta remota
     *
     * @param string $remoteFilePath Ruta completa del archivo a eliminar
     * @param string $remoteFileName Nombre del archivo
     * @return bool
     */
    public function deleteFile(string $remoteFilePath, string $remoteFileName): bool {
        return $this->fileService->deleteFile($this->siteId, $this->driveId, $remoteFilePath, $remoteFileName);
    }

    /**
     * Verifica si una carpeta existe en una ruta remota
     *
     * @param string $folderPath Ruta de la carpeta a verificar
     * @return bool
     */
    public function folderExists(string $folderPath): bool {
        return $this->fileService->folderExists($this->siteId, $this->driveId, $folderPath);
    }

    /**
     * Verifica si un archivo existe en una carpeta remota
     *
     * @param string $folderPath Ruta de la carpeta donde se buscará
     * @param string $fileName Nombre del archivo
     * @return bool
     */
    public function fileExistsInFolder(string $folderPath, string $fileName): bool {
        return $this->fileService->fileExistsInFolder($this->siteId, $this->driveId, $folderPath, $fileName);
    }
}
