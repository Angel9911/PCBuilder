<?php

namespace App\Private_lib\helpers;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class ComponentHelper
{

    /**
     * @throws Exception
     */
    public static function getUsbMotherboardHeaders(Connection $conn, int $motherboardId): array {

        $sql = "SELECT usb_header_type_id, quantity FROM motherboard_usb_headers WHERE motherboard_id = :id";

        $stmt = $conn->prepare($sql);

        $rows = $stmt->executeQuery(['id' => $motherboardId])->fetchAllAssociative();

        return $rows;
        //return array_column($rows, 'quantity', 'connector_type_id'); // [type_id => qty]
    }

    /**
     * @throws Exception
     */
    public static function getMotherboardUsbPorts(Connection $conn, int $motherboardId): array {
        $sql = "
        SELECT m.id AS motherboard_id,
               map.usb_port_type_id,
               SUM(mh.quantity * map.ports_per_header) AS total_ports
        FROM motherboard_usb_headers mh
        JOIN usb_header_port_map map ON map.usb_header_type_id = mh.usb_header_type_id
        JOIN motherboard m ON m.id = mh.motherboard_id
        WHERE mh.motherboard_id = :id
        GROUP BY m.id, map.usb_port_type_id
    ";

        $stmt = $conn->prepare($sql);
        $rows = $stmt->executeQuery(['id' => $motherboardId])->fetchAllAssociative();

        return array_column($rows, 'total_ports', 'usb_port_type_id'); // [port_type_id => qty]
    }

    /**
     * @throws Exception
     */
    public static function getPcCaseUsbPorts(Connection $conn, int $caseId): array {
        $sql = "SELECT usb_port_type_id, quantity
            FROM pc_case_usb_ports
            WHERE pc_case_id = :id";

        $stmt = $conn->prepare($sql);
        $rows = $stmt->executeQuery(['id' => $caseId])->fetchAllAssociative();

        return array_column($rows, 'quantity', 'usb_port_type_id'); // [port_type_id => qty]
    }

    /**
     * @throws Exception
     */
    public static function getGpuPowerConnectors(Connection $conn, int $gpuId): array {

        $sql = "SELECT connector_type_id, quantity FROM gpu_power_connectors WHERE gpu_id = :id";

        $stmt = $conn->prepare($sql);

        $rows = $stmt->executeQuery(['id' => $gpuId])->fetchAllAssociative();

        return array_column($rows, 'quantity', 'connector_type_id'); // [type_id => qty]
    }

    /**
     * @throws Exception
     */
    public static function getPsuPowerConnectors(Connection $conn, int $psuId): array {

        $sql = "SELECT connector_type_id, quantity FROM psu_power_connectors WHERE psu_id = :id";

        $stmt = $conn->prepare($sql);

        $rows = $stmt->executeQuery(['id' => $psuId])->fetchAllAssociative();

        return array_column($rows, 'quantity', 'connector_type_id'); // [type_id => qty]
    }

    /**
     * @throws Exception
     */
    public static function getGpuVideoOutputs(Connection $conn, int $gpuId): array {

        $sql = "SELECT output_type_id, quantity FROM gpu_video_outputs WHERE gpu_id = :id";

        $stmt = $conn->prepare($sql);

        $rows = $stmt->executeQuery(['id' => $gpuId])->fetchAllAssociative();

        return array_column($rows, 'quantity', 'output_type_id'); // [output_type_id => qty]
    }

    /**
     * @throws Exception
     */
    public static function getMonitorVideoInputs(Connection $conn, int $monitorId): array {

        $sql = "SELECT video_output_type_id, quantity FROM monitor_video_outputs WHERE monitor_id = :id";

        $stmt = $conn->prepare($sql);

        $rows = $stmt->executeQuery(['id' => $monitorId])->fetchAllAssociative();

        return array_column($rows, 'quantity', 'video_output_type_id'); // [type_id => qty]
    }

    /**
     * Fetch required USB header types for a PC case (used to match with motherboard headers)
     *
     * @param Connection $conn
     * @param int $pcCaseId
     * @return array [usb_type_id => quantity]
     * @throws Exception
     */
    public static function getCaseUsbHeaderTypes(Connection $conn, int $pcCaseId): array
    {
        // TODO: pc_case_usb_types is already dropped
        $sql = "SELECT usb_type_id, quantity 
            FROM pc_case_usb_types 
            WHERE pc_case_id = :id";

        $stmt = $conn->prepare($sql);

        $rows = $stmt->executeQuery(['id' => $pcCaseId])->fetchAllAssociative();

        return array_column($rows, 'quantity', 'usb_type_id'); // returns [type_id => quantity]
    }

    public static function calculateRemainingPower(?array $psu, array $components = [], int $buffer = 100): ?int {
        if (!$psu) {
            return null; // No PSU selected = no filtering
        }

        $totalPowerUsed = 0;

        foreach ($components as $comp) {
            $totalPowerUsed += $comp['power_wattage'] ?? 0;
        }

        return max($psu['power_wattage'] - $totalPowerUsed - $buffer, 0);
    }

    /**
     *
     * @throws Exception
     */
    public static function getMotherboardSlots(Connection $conn, int $motherboardId): array
    {
        $sql = "
            SELECT interface, lanes_count
            FROM motherboard_slots
            WHERE motherboard_id = :id
        ";

        $stmt = $conn->prepare($sql);

        $rows = $stmt->executeQuery(['id' => $motherboardId])->fetchAllAssociative();

        return $rows;
        // [
        //   ['interface' => 'M.2 PCIe 4.0', 'lanes_count' => 4],
        //   ['interface' => 'SATA3', 'lanes_count' => null]
        // ]
    }

    public static function isM2Pcie(string $interface): bool {

        return stripos($interface, 'M.2 PCIe') === 0;
    }

    public static function extractPcieVersion(string $interface): ?int {

        if (preg_match('/^M\.2 PCIe (\d+)/i', $interface, $m)) {

            return (int)$m[1];
        }
        return null; // не е PCIe
    }
}