<?php

namespace App\Repository;

use App\Constraints\ComponentCatalogFilter;
use App\Entity\Component;
use App\Private_lib\helpers\ComponentHelper;
use App\Private_lib\IndexableProductCache;
use App\Private_lib\trait\ProductDetailsTrait;
use App\Private_lib\trait\QueryFilterTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class ComponentRepository extends ServiceEntityRepository implements IndexableProductCache
{
    protected EntityManagerInterface $entityManager;

    use QueryFilterTrait;

    use ProductDetailsTrait;

    private static int $PAGE = 0;
    private static int $OFFSET = 0;

    private static array $COMPONENT_FILTERS = [];
    private static array $SELECTED_COMPONENTS = [];

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, Component::class);
        $this->entityManager = $entityManager;
    }

    public function findAllComponents(): array
    {
        $getAllComponents = $this->createQueryBuilder('c')
            ->innerJoin('c.type', 'ct') // Assuming Component has a relation to ComponentType
            ->select( 'ct.name AS component_type','c.name AS component_name')
            ->getQuery()
            ->getResult();

        $filteredResult = array_filter($getAllComponents, function ($element) {

            return $element['component_name'] != null ||
                $element['component_type'] != null;
        });

        $result = [];

        foreach ($filteredResult as $component) {

            if(!isset($result[$component['component_type']])) {

                $result[$component['component_type']] = [];
            }

            $result[$component['component_type']][] = $component['component_name'];

        }

        return $result;
    }

    public function updateComponentName(string $existingName, string $slugifyName): void
    {
        $this->createQueryBuilder('c')
            ->update(Component::class, 'c')
            ->set('c.slugify_name', ':slugifyName')
            ->where('c.name = :existingName')
            ->setParameter('existingName', $existingName)
            ->setParameter('slugifyName', $slugifyName)
            ->getQuery()
            ->execute();
    }

    public function findComponentById(int $id): Component
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @throws Exception
     */
    public function findComponentIdByComponentId(
        int $componentId,
        string $componentType,
        Connection $conn = null
    ): int
    {
        if ($conn === null) {
            $conn = $this->entityManager->getConnection();
        }

        $sql = "
        SELECT t.id 
        FROM {$componentType} t
        JOIN components comp ON comp.id = t.component_id
        JOIN component_types ct ON ct.id = comp.type_id
        WHERE comp.id = :component_id
    ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['component_id' => $componentId])->fetchOne();

        return $result ? (int)$result : 0;
    }

    /**
     * Find components by type (e.g., CPU, GPU, RAM)
     */
    public function findComponentsByType(string $type): array
    {

        $query = $this->createQueryBuilder('c')
            ->innerJoin('c.type', 'ct') // Assuming Component has a relation to ComponentType
            ->andWhere('ct.name = :type')
            ->setParameter('type', $type);

        if(in_array($type,['pc_case', 'monitor'])) {

            $query->leftJoin('c.images', 'img')
                ->addSelect('img');
        }

        return $query->getQuery()->getArrayResult();
    }

    /**
     * @throws Exception
     */
    public function findComponentSpecificationsByNameAndType(string $componentName, string $componentType): array
    {
        $details = $this->getProductDetailsBySlugifyName(
            $componentName,
            'components',
            $componentType,
            'component_id',
            'component_images',
            'component_id'
        );

        return $details;
    }

    public function findComponentNameBySlugifyName(string $slugifyName): array
    {
        return $this->createQueryBuilder('c')
            ->select( 'c.id AS component_id', ' c.name AS component_name')
            ->andWhere('c.slugifyName = :slugifyName')
            ->setParameter('slugifyName', $slugifyName)
            ->getQuery()
            ->getResult();
    }

    /**
     * Second algorithm for compatibility because the first algorithm generates many records and breaks the server
     * @param array $selected
     * @return array
     * @throws Exception
     */
    function findCompatibleComponents(array $selected): array
    {
        //TODO: move the logic from this method in service class
        $response = [];

        $connection = $this->entityManager->getConnection();
        // Get selected specs
        $cpu = isset($selected['cpu_id']) ? $this->getComponentSpecs('cpu', self::$PAGE, self::$OFFSET, self::$COMPONENT_FILTERS, self::$COMPONENT_FILTERS, $connection, $selected['cpu_id']) : null;
        $cpuCooler = isset($selected['cpu_cooling_id']) ? $this->getComponentSpecs('cpu_cooling', self::$PAGE, self::$OFFSET, self::$COMPONENT_FILTERS, self::$COMPONENT_FILTERS, $connection, $selected['cpu_cooling_id']) : null;
        $gpu = isset($selected['gpu_id']) ? $this->getComponentSpecs('gpu', self::$PAGE, self::$OFFSET, self::$COMPONENT_FILTERS, self::$COMPONENT_FILTERS, $connection, $selected['gpu_id']) : null;
        $monitor = isset($selected['monitor_id']) ? $this->getComponentSpecs('monitor', self::$PAGE, self::$OFFSET, self::$COMPONENT_FILTERS, self::$COMPONENT_FILTERS, $connection, $selected['monitor_id']) : null;
        $motherboard = isset($selected['motherboard_id']) ? $this->getComponentSpecs('motherboard', self::$PAGE, self::$OFFSET,self::$COMPONENT_FILTERS, self::$COMPONENT_FILTERS, $connection, $selected['motherboard_id']) : null;
        $case = isset($selected['pc_case_id']) ? $this->getComponentSpecs('pc_case', self::$PAGE, self::$OFFSET, self::$COMPONENT_FILTERS, self::$COMPONENT_FILTERS, $connection, $selected['pc_case_id']) : null;
        $ram = isset($selected['ram_id']) ? $this->getComponentSpecs('ram', self::$PAGE, self::$OFFSET, self::$COMPONENT_FILTERS, self::$COMPONENT_FILTERS, $connection, $selected['ram_id']) : null;
        $storage = isset($selected['storage_id']) ? $this->getComponentSpecs('storage', self::$PAGE, self::$OFFSET, self::$COMPONENT_FILTERS, self::$COMPONENT_FILTERS, $connection, $selected['storage_id']) : null;
        $psu = isset($selected['psu_id']) ? $this->getComponentSpecs('psu', self::$PAGE, self::$OFFSET, self::$COMPONENT_FILTERS, self::$COMPONENT_FILTERS, $connection, $selected['psu_id']) : null;
        //TODO: if choose first psu and then other component with power_wattage(cpu,gpu,monitor) it will not calculate the required power_wattage

        // Get compatible parts
        $response['cpu_ids'] = $this->getCompatibleCPUs($connection, $cpu, $motherboard, $ram, $psu, $gpu, $monitor); // check
        $response['cpu_cooling_ids'] = $this->getCompatibleCpuCooler($connection, $cpuCooler, $cpu, $case); // check
        $response['motherboard_ids'] = $this->getCompatibleMotherboards($connection, $motherboard, $cpu, $ram, $storage, $case, $gpu);
        $response['ram_ids'] = $this->getCompatibleRAM($connection, $ram, $cpu, $motherboard); // check
        $response['gpu_ids'] = $this->getCompatibleGPUs($connection, $gpu, $motherboard, $case, $psu, $cpu, $monitor); // check
        $response['storage_ids'] = $this->getCompatibleStorage($connection, $storage, $motherboard); // check
        $response['psu_ids'] = $this->getCompatiblePSUs($connection, $cpu, $gpu, $monitor, $case, $storage, $motherboard);// check
        $response['pc_case_ids'] = $this->getCompatibleCases($connection, $case, $motherboard, $gpu, $psu, $cpuCooler, $storage); // check
        $response['monitor_ids'] = $this->getCompatibleMonitors($connection, $monitor, $psu, $cpu, $gpu); // check

        return $response;
    }

    /**
     * @throws Exception
     */
    function getComponentSpecs(string $type
        , int $limit = 0
        , int $offset = 0
        , array $filters=[]
        , array $selectedComponents = []
        , Connection $conn = null
        , int $id = 0
    ): ?array
    {
        $whereSql = '';
        // If no connection is passed, use default from service container
        if ($conn === null) {

            $conn = $this->entityManager->getConnection();
        }

        $params = [];

        if($id > 0){

            $conditions[] = "t.component_id = :id";

            $params['id'] = $id;

            $whereSql = ' WHERE '.implode(' AND ', $conditions);
        }

        if(!empty($filters)){

            $cfg = ComponentCatalogFilter::get($type);

            $filtersCfg = $cfg['filters'] ?? [];

            $whereSql = $this->buildQueryFilter($filtersCfg, $filters, 't', $params);
        }

        $sql = "
            SELECT t.*, c.name, c.slugify_name, ct.name AS component_type, cb.name AS brand
            FROM {$type} t
            JOIN components c ON c.id = t.component_id
            JOIN component_brands cb ON cb.id = c.brand_id
            JOIN component_types ct ON ct.id = c.type_id
            $whereSql
        ";

        $sql .= $this->buildPaginationClauseSql($limit, $offset);

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery($params);

        return $id > 0 ? ($result->fetchAssociative() ?: []) : $result->fetchAllAssociative();

    }

    /**
     * @throws Exception
     */
    function getTotalsCountComponent(string $type): int
    {
        return $this->getProductsTypeCount($type);
    }

    /**
     * @throws Exception
     */
    function getCompatibleMonitors($conn, ?array $selectedMonitor
        , ?array $psu
        , ?array $cpu // used only if the psu is selected(sum power wattage)
        , ?array $gpu // used only if the psu is selected(sum power wattage)
    ): array {
        $results = [];

        $conditions = [];
        $params = [];

        if ($selectedMonitor) {
            // Exclude the selected monitor from results
            $conditions[] = "m.component_id != :selected_id";
            $params['selected_id'] = $selectedMonitor['component_id'];

            $results[] = ['component_id' => $selectedMonitor['component_id']
                , 'name' => $selectedMonitor['name'] ?? 'Selected Monitor'
                , 'power_wattage' => $selectedCpu['power_wattage'] ?? '0'
            ];
        }

        $otherComponents = [];

        if ($cpu) {

            $otherComponents[] = $cpu;
        }
        if ($gpu) {

            $otherComponents[] = $gpu;
        }

        $maxMonitorPower = ComponentHelper::calculateRemainingPower($psu, $otherComponents);

        if ($maxMonitorPower !== null) {

            $conditions[] = "m.power_wattage <= :max_monitor_power";
            $params['max_monitor_power'] = $maxMonitorPower;
        }

        $whereSql = '';

        if(!empty($conditions)){

            $whereSql = 'WHERE '. implode(" AND ", $conditions);
        }

        $sql = "SELECT m.component_id, comp.name, comp.power_wattage 
                FROM monitor m 
                JOIN components comp ON comp.id = m.component_id
                 {$whereSql}
        ";

        $stmt = $conn->prepare($sql);

        $results = array_merge($results, $stmt->executeQuery($params)->fetchAllAssociative());

        if ($gpu) {

            $gpuId = $this->findComponentIdByComponentId($gpu['component_id'], 'gpu', $conn);
            $gpuOutputs = ComponentHelper::getGpuVideoOutputs($conn, $gpuId); //$this->getGpuVideoOutputs($conn, $gpuId);

            $filteredResult = [];

            foreach ($results as $monitor) {

                $monitorId = $this->findComponentIdByComponentId($monitor['component_id'], 'monitor', $conn);
                $monitorInputs = ComponentHelper::getMonitorVideoInputs($conn, $monitorId);

                $compatible = false;

                foreach ($monitorInputs as $type => $neededQty) {

                    $type = (int) $type;

                    if (!array_key_exists($type, $gpuOutputs)) {
                        // GPU does not support this input type at all — skip the check
                        continue;
                    }

                    if ($gpuOutputs[$type] >= $neededQty) { // TODO: This is not important for compatibility except when user want to connect more than 1 monitor

                        $compatible = true;
                    }
                }

                if ($compatible) {

                    $filteredResult[] = $monitor;
                }
            }

            return $filteredResult;
        }

        return $results;
    }

    /**
     * @throws Exception
     */
    function getCompatibleCases($conn
        , ?array $selectedCase
        , ?array $mb
        , ?array $gpu
        , ?array $psu
        , ?array $cpuCooler
        , ?array $storage
    ): array {
        $conditions = []; // used for where clause in sql
        $params = []; // pass parameters for filtering

        $results = [];

        if ($selectedCase) {
            $conditions[] = "pc.component_id != :selected_id";
            $params['selected_id'] = $selectedCase['component_id'];

            $results[] = ['component_id' => $selectedCase['component_id']
                , 'name' => $selectedCase['name'] ?? 'Selected Case'
                , 'power_wattage' => $selectedCpu['power_wattage'] ?? '0'
            ];
        }

        if ($mb) {

            $conditions[] = "
            EXISTS (
                SELECT 1
                FROM pc_case_psu_form_factors cf         
                WHERE cf.pc_case_id = pc.id
                    AND cf.psu_form_factor_id = :form_factor_id
            )";

            $params['form_factor_id'] = $mb['form_factor_id'];

            $motherboardUsbTypes = ComponentHelper::getUsbMotherboardHeaders($conn, $mb['id']);// $this->getUsbMotherboardHeaders($conn, $mb['id']);

            foreach ($motherboardUsbTypes as $i => $type) {
                $conditions[] = "EXISTS (
                SELECT 1 FROM pc_case_usb_types u
                WHERE u.pc_case_id = pc.id
                  AND u.usb_type_id = :usb_type_{$i}
                  AND u.quantity >= :usb_qty_{$i}
            )";
                $params["usb_type_{$i}"] = $type['usb_header_type_id'];
                $params["usb_qty_{$i}"] = $type['quantity'];
            }
        }

        if ($gpu) {

            $conditions[] = "pc.gpu_clearance_mm >= :length_mm";
            $params['length_mm'] = $gpu['length_mm'];

            $conditions[] = "pc.expansion_slots >= :slot_width";
            $params['slot_width'] = $gpu['slot_width'];
        }

        if ($psu) {

            $conditions[] = "pc.psu_length_limit_mm IS NULL OR pc.psu_length_limit_mm >= :psu_length";
            $params['psu_length'] = $psu['length_mm'] ?? 0;
        }

        if ($cpuCooler) {

            $conditions[] = "pc.max_cooler_height_mm IS NULL OR pc.max_cooler_height_mm >= :cooler_height";
            $params['cooler_height'] = $cpuCooler['height_mm'] ?? 0;
        }

        if ($storage && isset($storage['form_factor'])) {

            if (str_starts_with($storage['form_factor'], '2.5')) {

                $conditions[] = "pc.internal_2_5_bays > 0";

            } elseif (str_starts_with($storage['form_factor'], '3.5')) {

                $conditions[] = "pc.internal_3_5_bays > 0";
            }
        }
        $whereSql = '';

        if(!empty($conditions)){

            $whereSql = 'WHERE '. implode(" AND ", $conditions);
        }

        $sql = "
            SELECT pc.component_id, comp.name, comp.power_wattage
            FROM pc_case pc
            JOIN components comp ON comp.id = pc.component_id
            {$whereSql}
        ";

        $stmt = $conn->prepare($sql);

        $results = array_merge($results, $stmt->executeQuery($params)->fetchAllAssociative());

        return $results;

    }

    /**
     * @throws Exception
     */
    function getCompatiblePSUs($conn
        , ?array $cpu
        , ?array $gpu
        , ?array $monitor
        , ?array $pcCase
        , ?array $storage
        , ?array $motherboard): array {
        $conditions = []; // used for where clause in sql
        $params = []; // pass parameters for filtering

        // Step 1: Power Budget Calculation
        $requiredPower =
            ($cpu['power_wattage'] ?? 0) +
            ($gpu['power_wattage'] ?? 0) +
            ($monitor['power_wattage'] ?? 0) + 100;

        if ($requiredPower > 0) {
            $conditions[] = "psu.power_wattage >= :required_power";
            $params['required_power'] = $requiredPower;
        }

        // PC CASE compatibility (Form Factor + Length)
        if ($pcCase) {
            $conditions[] = "psu.length_mm <= :psu_length_limit";
            $params['psu_length_limit'] = $pcCase['psu_length_limit_mm'] ?? 150;

            $conditions[] = "EXISTS (
            SELECT 1 FROM pc_case_psu_form_factors pf
            WHERE pf.pc_case_id = :pc_case_id
              AND pf.psu_form_factor_id = psu.form_factor_id
        )";
            $params['pc_case_id'] = $pcCase['id'] ?? 0;
        }

        $whereSql = '';
        if (!empty($conditions)) {
            $whereSql = 'WHERE ' . implode(' AND ', $conditions);
        }

        $sql = "
        SELECT psu.id, psu.component_id, comp.name, comp.power_wattage
        FROM psu
        JOIN components comp ON comp.id = psu.component_id
        {$whereSql}
    ";

        $stmt = $conn->prepare($sql);
        $initialResults = $stmt->executeQuery($params)->fetchAllAssociative();

        $filteredResults = [];

        // TODO: It needs to be checked!!!

        foreach ($initialResults as $psu) {
            $psuId = $psu['id'];

            // Fetch PSU connectors
            $psuConnectors = ComponentHelper::getPsuPowerConnectors($conn, $psuId);//$this->getPsuPowerConnectors($conn, $psuId);

            // Validate GPU connectors
            if ($gpu) {

                $gpuConnectors = ComponentHelper::getGpuPowerConnectors($conn, $gpu['id'] ?? 0); //$this->getGpuPowerConnectors($conn, $gpu['id'] ?? 0);

                foreach ($gpuConnectors as $type => $needed) {

                    if (($psuConnectors[$type] ?? 0) < $needed) {
                          continue 2;
                    }
                }
            }

            // Validate Motherboard (ATX 24-pin + EPS 8-pin)
            if ($motherboard) {
                $requiredMbConnectors = [
                    1 => 1, // ATX 24-pin
                    2 => 1  // EPS 8-pin
                ];
                foreach ($requiredMbConnectors as $type => $needed) {
                    if (($psuConnectors[$type] ?? 0) < $needed) {
                        continue 2;
                    }
                }
            }
            // TODO: You could later evolve this to use motherboard fields like requires_dual_eps boolean to make it dynamic.

            /* Validate Storage (SATA Power)
             * If storage.connector_type === 'SATA', the PSU must have at least one SATA Power (id = 7).
                Other types like M.2 typically don’t need external power from PSU (they draw directly from motherboard).
                We don’t compare storage.connector_type to psu_power_connector.connector_type_id directly by name (like "SATA" vs "SATA Power")
             */
            if ($storage && $storage['connector_type'] === 'SATA') {
                if (($psuConnectors[7] ?? 0) < 1) { // 7: SATA Power
                    continue;
                }
            }

            // If all compatibility checks pass
            $filteredResults[] = $psu;
        }

        return $filteredResults;
    }

    function getCompatibleStorage($conn, ?array $selectedStorage, ?array $mb): array {
        $conditions = []; // used for where clause in sql
        $params = []; // pass parameters for filtering

        $results = [];

        if($selectedStorage){
            $conditions[] = "s.component_id != :selected_id";
            $params['selected_id'] = $selectedStorage['component_id'];

            $results[] = ['component_id' => $selectedStorage['component_id']
                , 'name' => $selectedStorage['name'] ?? 'Selected Storage'
                , 'power_wattage' => $selectedCpu['power_wattage'] ?? '0'
            ];
        }

        if($mb){

            // TODO: Maybe this will be replace because the following checks are more detailed
            $storageInterfaces = str_getcsv(trim($mb['storage_interfaces'], '{}'));

            if(!empty($storageInterfaces)){

                $conditions[] = "s.interface = ANY(:interfaces::text[])";
                $params['interfaces'] = '{' . implode(',', $storageInterfaces) . '}';
            }

            // Bus type compatibility (PCIe, SATA, etc.)
            if (!empty($mb['supported_bus_types'])) {
                $busTypes = str_getcsv(trim($mb['supported_bus_types'], '{}'));
                $conditions[] = "s.bus_type = ANY(:bus_types::text[])";
                $params['bus_types'] = '{' . implode(',', $busTypes) . '}';
            }

            // Connector compatibility (M.2, SATA, etc.)
            if (!empty($mb['supported_connector_types'])) {
                $connectors = str_getcsv(trim($mb['supported_connector_types'], '{}'));
                $conditions[] = "s.connector_type = ANY(:connectors::text[])";
                $params['connectors'] = '{' . implode(',', $connectors) . '}';
            }

            // PCIe version filter for PCIe drives
            $conditions[] = "(s.bus_type != 'PCIe' OR s.pcie_version IS NULL OR s.pcie_version <= :mb_pcie_version)";
            $params['mb_pcie_version'] = $mb['pcie_version'];

            // NVMe compatibility
            $conditions[] = "(s.nvme = false OR :nvme_supported = true)";
            $params['nvme_supported'] = $mb['supports_nvme'];

            // M.2 slot availability
            $conditions[] = "(s.connector_type != 'M.2' OR :mb_m2_slots > 0)";
            $params['mb_m2_slots'] = $mb['m2_slots'];

            // SATA port availability
            $conditions[] = "(s.connector_type != 'SATA' OR :mb_sata_ports > 0)";
            $params['mb_sata_ports'] = $mb['sata_ports'];
        }

        $whereSql = '';

        if(!empty($conditions)){

            $whereSql = 'WHERE '. implode(" AND ", $conditions);
        }

        $sql = "
            SELECT s.component_id, comp.name, comp.power_wattage
            FROM storage s
            JOIN components comp ON comp.id = s.component_id
            {$whereSql}
        ";

        $stmt = $conn->prepare($sql);

        $results = array_merge($results, $stmt->executeQuery($params)->fetchAllAssociative());

        return $results;
    }

    /**
     * @throws Exception
     */
    function getCompatibleGPUs($conn
        , ?array $selectedGpu
        , ?array $mb
        , ?array $case
        , ?array $psu
        , ?array $cpu // used only if the psu is selected(sum power wattage)
        , ?array $monitor // used only if the psu is selected(sum power wattage)
    ): array {

        $conditions = []; // used for where clause in sql
        $params = []; // pass parameters for filtering

        $results = [];

        // ✅ Define GPU-to-PSU connector compatibility map
        $connectorCompatibility = [
            3 => [3, 4], // 6-pin fulfilled by 6-pin or 6+2
            5 => [5, 4], // 8-pin fulfilled by 8-pin or 6+2
            6 => [6],    // 12VHPWR exact match only
            4 => [4],    // 6+2-pin exact
        ];

        if ($selectedGpu) {
            $conditions[] = "gpu.component_id != :selected_id";
            $params['selected_id'] = $selectedGpu['component_id'];

            $results[] = ['component_id' => $selectedGpu['component_id']
                , 'name' => $selectedGpu['name'] ?? 'Selected GPU'
                , 'power_wattage' => $selectedCpu['power_wattage'] ?? '0'
            ];
        }

        if ($mb) {

            $conditions[] = "gpu.pcie_version <= :pcie_version";
            $params['pcie_version'] = $mb['pcie_version'];
        }

        if ($case) {

            $conditions[] = "gpu.length_mm <= :gpu_clearance";
            $params['gpu_clearance'] = $case['gpu_clearance_mm'];
        }

        $otherComponents = [];

        if ($cpu) {

            $otherComponents[] = $cpu;
        }
        if ($monitor) {

            $otherComponents[] = $monitor;
        }

        $maxGpuPower = ComponentHelper::calculateRemainingPower($psu, $otherComponents);

        if ($maxGpuPower !== null) {

            $conditions[] = "gpu.power_wattage <= :max_gpu_power";
            $params['max_gpu_power'] = $maxGpuPower;
        }

        $whereSql = '';

        if(!empty($conditions)){

            $whereSql = 'WHERE '. implode(" AND ", $conditions);
        }

        $sql = "
        SELECT gpu.component_id, comp.name, comp.power_wattage
        FROM gpu
        JOIN components comp ON comp.id = gpu.component_id
        {$whereSql}
        ";

        $stmt = $conn->prepare($sql);

        $results = array_merge($results, $stmt->executeQuery($params)->fetchAllAssociative());

        $filteredResults = [];

        // TODO: It needs to be checked!!!

        foreach ($results as $gpu) {

            $gpuId = $this->findComponentIdByComponentId($gpu['component_id'], 'gpu', $conn);

            // === PSU Connector Check ===
            if ($psu) {

                $gpuConnectors = ComponentHelper::getGpuPowerConnectors($conn, $gpuId);
                $psuConnectors = ComponentHelper::getPsuPowerConnectors($conn, $psu['id'] ?? 0);

                foreach ($gpuConnectors as $requiredType => $requiredQty) {

                    $compatibleTypes = $connectorCompatibility[$requiredType] ?? [$requiredType];

                    $availableQty = 0;
                    foreach ($compatibleTypes as $psuType) {

                        $availableQty += $psuConnectors[$psuType] ?? 0;
                    }

                    if ($availableQty < $requiredQty) {

                        continue 2; // Skip incompatible GPU
                    }
                }
            }

            // === Monitor Output Check ===
            if ($monitor) {

                $gpuOutputs = ComponentHelper::getGpuVideoOutputs($conn, $gpuId);
                $monitorInputs = ComponentHelper::getMonitorVideoInputs($conn, $monitor['id'] ?? 0);

                $compatible = false;

                foreach ($monitorInputs as $type => $qty) {
                    $type = (int)$type;

                    if (!isset($gpuOutputs[$type])) {
                        continue; // GPU doesn’t support this port type
                    }

                    if ($gpuOutputs[$type] >= $qty) {
                        $compatible = true;
                        break; // Found at least one matching port in enough quantity
                    }
                }

                if (!$compatible) {
                    continue; // Reject this GPU
                }
            }

            // Optional: Check GPU fits within case expansion slots
            if ($case && isset($gpu['slot_width']) && isset($case['expansion_slots'])) {

                if ($gpu['slot_width'] > $case['expansion_slots']) {
                    continue; // Skip if too wide
                }
            }

            // If all passes, add to final result
            $filteredResults[] = $gpu;
        }

        return $filteredResults;
    }

    /**
     * @throws Exception
     */
    function getCompatibleRAM($conn
        , ?array $selectedRam
        , ?array $cpu
        , ?array $mb
    ): array {
        $conditions = []; // used for where clause in sql
        $params = []; // pass parameters for filtering

        $results = [];
        /* TODO:
         * memory_channels → if ram.modules < cpu.memory_channels * 2, display a warning ("Memory will run in single-channel mode").

            You can also add a check for oc_profile_support → if RAM supports XMP, but MB only supports EXPO → warning
        ("This memory will only work in JEDEC mode, the XMP profile is not supported by the motherboard").
         */
        if ($selectedRam) {
            $conditions[] = "ram.component_id != :selected_id";
            $params['selected_id'] = $selectedRam['component_id'];

            // if only ram is passed
            $results[] = [
                'component_id' => $selectedRam['component_id'],
                'name' => $selectedRam['name'] ?? 'Selected RAM',
                'power_wattage' => $selectedCpu['power_wattage'] ?? '0'
            ];
        }

        if ($cpu) {

            $conditions[] = "ram.type = :cpu_type";
            $params['cpu_type'] = $cpu['memory_type'];

            $conditions[] = "ram.capacity_gb <= :max_memory_gb";
            $params['max_memory_gb'] = $cpu['max_memory_gb'];

            /* TODO : CHECK if this ecc_support actually need
             * if ($cpu['ecc_support'] === false) {

                $conditions[] = "(ram.ecc IS FALSE OR ram.ecc IS NULL)";
            } else {

                $conditions[] = "ram.ecc = TRUE";
            }*/
        }

        if ($mb) {

            $conditions[] = "ram.type = :mb_type";
            $params['mb_type'] = $mb['memory_type'];

            $conditions[] = "ram.modules <= :slots";
            $params['slots'] = $mb['memory_slots'];

            $conditions[] = "ram.capacity_gb <= :max_capacity";
            $params['max_capacity'] = $mb['max_memory_supported'];

           /* TODO : CHECK if this ecc_support actually need
           $conditions[] = "ram.ecc = :ecc_support";
            $params['ecc_support'] = $mb['ecc_support'];*/

            // Speed check
            $ocSpeeds = str_getcsv(trim($mb['oc_memory_speeds'], '{}'));

            $conditions[] = "(
            ram.jedec_speed <= :base_speed
                OR
            ram.max_xmp_speed = ANY(:oc_speeds::int[])
            )";

            $params['base_speed'] = $mb['base_memory_speed'];
            $params['oc_speeds'] = '{' . implode(',', $ocSpeeds) . '}';

            if(isset($mb['form_factor_id'])){

                $allFormFactors = $this->getFormFactorMap($conn);

                $mb['form_factor_name'] = $allFormFactors[$mb['form_factor_id']];

                if ($mb['form_factor_name'] === 'Mini-ITX') {

                    $conditions[] = "ram.form_factor = 'SO-DIMM'";

                } else {
                    $conditions[] = "ram.form_factor = '288-pin DIMM'";
                }
            }
        }

        $whereSql = '';

        if(!empty($conditions)){

            $whereSql = 'WHERE '. implode(" AND ", $conditions);
        }

        $sql = "
        SELECT ram.component_id, comp.name, comp.power_wattage
        FROM ram
        JOIN components comp ON comp.id = ram.component_id
        {$whereSql}
    ";

        $stmt = $conn->prepare($sql);

        $results = array_merge($results, $stmt->executeQuery($params)->fetchAllAssociative());

        return $results;
    }

    /**
     * @throws Exception
     */
    function getCompatibleMotherboards(
        Connection $conn,
        ?array $selectedMb,
        ?array $cpu,
        ?array $ram,
        ?array $storage,
        ?array $pcCase,
        ?array $gpu
    ): array {
        $conditions = [];
        $params = [];
        $results = [];

        if ($selectedMb) {
            $conditions[] = "mb.component_id != :selected_id";
            $params['selected_id'] = $selectedMb['component_id'];

            $results[] = [
                'component_id' => $selectedMb['component_id'],
                'name' => $selectedMb['name'] ?? 'Selected Motherboard',
                'power_wattage' => $selectedMb['power_wattage'] ?? '0'
            ];
        }

        if ($cpu) {
            $conditions[] = "mb.socket = :socket";
            $params['socket'] = $cpu['socket'];

            $chipsets = str_getcsv(trim($cpu['chipset'], '{}'));
            if (!empty($chipsets)) {
                $conditions[] = "mb.chipset = ANY(:cpu_chipsets::text[])";
                $params['cpu_chipsets'] = '{' . implode(',', $chipsets) . '}';
            }
        }

        if ($ram) {
            $conditions[] = "mb.memory_type = :memory_type";
            $params['memory_type'] = $ram['type'];

            $conditions[] = "mb.memory_slots >= :modules";
            $params['modules'] = $ram['modules'];

            $conditions[] = "mb.max_memory_supported >= :capacity_gb";
            $params['capacity_gb'] = $ram['capacity_gb'];

            // Speed compatibility
            $conditions[] = "(
                mb.base_memory_speed >= :jedec_speed
                    OR
                :max_xmp_speed = ANY(mb.oc_memory_speeds)
            )";

            $params['jedec_speed'] = $ram['jedec_speed'];
            $params['max_xmp_speed'] = $ram['max_xmp_speed'];

            /*if (isset($ram['ecc']) && $ram['ecc']) {
                $conditions[] = "mb.ecc_support = true";
            }*/
        }

        if ($storage) {
            $conditions[] = ":interface = ANY(mb.storage_interfaces)";
            $params['interface'] = $storage['interface'];

            if ($storage['connector_type'] === 'SATA') {
                $conditions[] = "mb.sata_ports >= 1";
            }

            if ($storage['connector_type'] === 'M.2') {
                $conditions[] = "mb.m2_slots >= 1";
            }

            if ($storage['nvme'] === true) {
                $conditions[] = "mb.supports_nvme = true";
            }

            if (!empty($storage['bus_type'])) {
                $conditions[] = ":bus_type = ANY(mb.supported_bus_types)";
                $params['bus_type'] = $storage['bus_type'];
            }

            if (!empty($storage['connector_type'])) {
                $conditions[] = ":conn_type = ANY(mb.supported_connector_types)";
                $params['conn_type'] = $storage['connector_type'];
            }
        }

        if ($pcCase) {
            $conditions[] = "EXISTS (
            SELECT 1 FROM pc_case_form_factors cf
            WHERE cf.pc_case_id = :case_id
              AND cf.form_factor_id = mb.form_factor_id
        )";
            $params['case_id'] = $pcCase['component_id'];

            $caseUsbTypes = ComponentHelper::getCaseUsbHeaderTypes($conn, $pcCase['id'] ?? 0);

            if ($caseUsbTypes) {
                foreach ($caseUsbTypes as $usbTypeId => $qty) {
                    $conditions[] = "EXISTS (
                    SELECT 1 FROM motherboard_usb_headers mu
                    WHERE mu.motherboard_id = mb.id
                    AND mu.usb_header_type_id = :usb_type_$usbTypeId
                    AND mu.quantity >= :usb_qty_$usbTypeId
                )";
                    $params["usb_type_$usbTypeId"] = $usbTypeId;
                    $params["usb_qty_$usbTypeId"] = $qty;
                }
            }
        }

        if ($gpu) {
            $conditions[] = "mb.pcie_version >= :gpu_pcie_version";
            $params['gpu_pcie_version'] = $gpu['pcie_version'];
            //$gpuInterface = strtolower($gpu['interface'] ?? '');

            $gpuLanes = $gpu['lanes'] ?? 16;

            $conditions[] = "EXISTS (
            SELECT 1 FROM motherboard_pcie_slots ps
            WHERE ps.motherboard_id = mb.id
            AND ps.gen::float >= :gpu_pcie_version
            AND ps.lanes >= :gpu_lanes
        )";
            $params['gpu_lanes'] = $gpuLanes;
        }

        $whereSql = '';
        if (!empty($conditions)) {
            $whereSql = 'WHERE ' . implode(" AND ", $conditions);
        }

        $sql = "
        SELECT mb.component_id, comp.name, comp.power_wattage
        FROM motherboard mb
        JOIN components comp ON comp.id = mb.component_id
        {$whereSql}
    ";

        $stmt = $conn->prepare($sql);
        $results = array_merge($results, $stmt->executeQuery($params)->fetchAllAssociative());

        return $results;
    }

    function getCompatibleCPUs( $conn
        , ?array $selectedCpu
        , ?array $motherboard
        , ?array $ram
        , ?array $psu
        , ?array $gpu // used only if the psu is selected(sum power wattage)
        , ?array $monitor // used only if the psu is selected(sum power wattage)
    ): array {
        $conditions = []; // used for where clause in sql
        $params = []; // pass parameters for filtering

        $results = [];

        // TODO: Considering about include radiator size compatibility or if we plan to handle water cooling differently!

        if ($selectedCpu) {
            $conditions[] = "cpu.component_id != :selected_id";
            $params['selected_id'] = $selectedCpu['component_id'];

            $results[] = [
                'component_id' => $selectedCpu['component_id']
                , 'name' => $selectedCpu['name'] ?? 'Selected CPU'
                , 'power_wattage' => $selectedCpu['power_wattage'] ?? '0'
            ];
        }

        if($motherboard){

            $conditions[] = "cpu.socket = :socket";
            $params['socket'] = $motherboard['socket'];

            $conditions[] = ":chipset = ANY(cpu.chipset)";
            $params['chipset'] =  $motherboard['chipset'];
        }

        if($ram){

            $conditions[] = "cpu.memory_type = :type";
            $params['type'] = $ram['type'];

            $conditions[] = "cpu.max_memory_gb >= :capacity_gb";
            $params['capacity_gb'] = $ram['capacity_gb'];

            /* TODO : CHECK if this ecc_support actually need
            $conditions[] = "cpu.ecc_support = :ecc";
            $params['ecc'] = $ram['ecc'];*/
        }

        $otherComponents = [];

        if ($gpu) {

            $otherComponents[] = $gpu;
        }
        if ($monitor) {

            $otherComponents[] = $monitor;
        }

        $maxCpuPower = ComponentHelper::calculateRemainingPower($psu, $otherComponents);

        if ($maxCpuPower !== null) {

            $conditions[] = "cpu.power_wattage <= :max_cpu_power";
            $params['max_cpu_power'] = $maxCpuPower;
        }

        $whereSql = '';

        if(!empty($conditions)){

            $whereSql = 'WHERE '. implode(" AND ", $conditions);
        }

        // Build final query
        $sql = "
        SELECT cpu.component_id, comp.name,  comp.power_wattage
        FROM cpu
        JOIN components comp ON comp.id = cpu.component_id
        {$whereSql}
        ";

        $stmt = $conn->prepare($sql);

        $results = array_merge($results, $stmt->executeQuery($params)->fetchAllAssociative());

        return $results;
    }

    function getCompatibleCpuCooler($conn
        , ?array $selectedCooler
        , ?array $cpu
        , ?array $case
    ): array
    {
        $conditions = []; // WHERE conditions
        $params = [];     // Query parameters
        $results = [];

        // Step 1: Exclude selected cooler from results and optionally return it first
        if ($selectedCooler) {
            $conditions[] = "cooling.component_id != :selected_id";
            $params['selected_id'] = $selectedCooler['component_id'];

            $results[] = [
                'component_id' => $selectedCooler['component_id'],
                'name' => $selectedCooler['name'] ?? 'Selected CPU Cooler',
                'power_wattage' => $selectedCooler['power_wattage'] ?? 0
            ];
        }

        // Step 2: CPU Compatibility (socket + optional TDP)
        if ($cpu) {

            // Check socket compatibility (cpu.socket must exist in cpu_cooling.supported_sockets[])
            $conditions[] = ":cpu_socket = ANY(cooling.supported_sockets)";
            $params['cpu_socket'] = $cpu['socket'];

            // Optional TDP filtering for performance guidance
            if (isset($cpu['tdp'])) {

                // Only allow coolers that can handle moderate to high TDP
                $conditions[] = "(cooling.max_fan_rpm >= 1200 OR :cpu_tdp <= 90)";
                $params['cpu_tdp'] = $cpu['tdp'];
            }
        }

        // Step 3: Case Compatibility (cooler height)
        if ($case && isset($case['max_cooler_height_mm'])) {

            // Ensure the cooler fits within case height constraints
            $conditions[] = "(cooling.height_mm IS NULL OR cooling.height_mm <= :max_cooler_height)";
            $params['max_cooler_height'] = $case['max_cooler_height_mm'];
        }

        // Build the WHERE clause
        $whereSql = '';
        if (!empty($conditions)) {
            $whereSql = 'WHERE ' . implode(' AND ', $conditions);
        }

        // Final SQL Query
        $sql = "
        SELECT cooling.component_id, comp.name, comp.power_wattage
        FROM cpu_cooling cooling
        JOIN components comp ON comp.id = cooling.component_id
        {$whereSql}
    ";

        $stmt = $conn->prepare($sql);

        $results = array_merge($results, $stmt->executeQuery($params)->fetchAllAssociative());

        return $results;
    }

    /**
     * @throws Exception
     */
    private function getFormFactorMap(Connection $conn): array {

        $sql = "SELECT id, name FROM form_factors";

        $stmt = $conn->prepare($sql);

        $results = $stmt->executeQuery()->fetchAllAssociative();

        $formFactors = [];
        foreach ($results as $row) {
            $formFactors[$row['id']] = $row['name'];
        }
        return $formFactors;
    }

    /**
     * @throws Exception
     */
    public function getProductsIdsByType(string $type): array
    {
        $conn = $this->entityManager->getConnection();

        $sql = "SELECT c.id
            FROM components c
            JOIN component_types ct ON ct.id = c.type_id
            WHERE ct.name = :type
            ORDER BY c.id ASC";

        $ids = $conn->executeQuery($sql, ['type' => $type])->fetchFirstColumn();

        // return int[]
        return array_map('intval', $ids);
    }

    /**
     * @throws Exception
     */
    public function getProductSpecsByTypeAndIds(string $peripheryType, array $ids): array
    {
        if (empty($ids)) return [];

        $conn = $this->entityManager->getConnection();

        $sql = "
        SELECT
            t.*,
            c.id          AS component_id,
            c.name,
            c.slugify_name,
            ct.name       AS component_type
        FROM {$peripheryType} t
        JOIN components       c  ON c.id = t.component_id
        JOIN component_types  ct ON ct.id = c.type_id
        WHERE c.id = ANY(:ids)
        ";


        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery(['ids' => $ids])->fetchAllAssociative();
    }

    /**
     * @throws Exception
     */
    public function getAndLoadProductFiltersByType(string $type, array $productsIds = []): array
    {
        $conn = $this->entityManager->getConnection();

        $cfg = ComponentCatalogFilter::get($type);
        $table = $cfg['table'];
        $filtersCfg = $cfg['filters'] ?? [];

        $baseFrom  = "FROM {$table} t";
        // Always-on joins for this category (e.g., components c)
        if (!empty($cfg['base_joins'])) {
            foreach ($cfg['base_joins'] as $j) {
                $baseFrom .= " {$j['type']} JOIN {$j['table']} {$j['alias']} ON {$j['on']}";
            }
        }

        $baseWhere = "WHERE 1=1";
        $params    = [];

        if (!empty($productsIds)) {
            $baseWhere .= " AND t.component_id = ANY(:compat_ids)";
            $params['compat_ids'] = '{' . implode(',', array_map('intval', $productsIds)) . '}';
        }

        $out = [];

        foreach ($filtersCfg as $key => $f) {
            if (isset($f['enabled']) && $f['enabled'] === false) {
                continue;
            }

            $label  = $f['label'] ?? ucfirst(str_replace('_', ' ', $key));
            $kind   = $f['kind'];
            $source = $f['source'];

            // FROM for this facet
            $from = $baseFrom;
            if (!empty($f['joins'])) {
                foreach ($f['joins'] as $j) {
                    $from .= " {$j['type']} JOIN {$j['table']} {$j['alias']} ON {$j['on']}";
                }
            }
            if ($source === 'junction' && !empty($f['junction'])) {
                foreach ($f['junction'] as $j) {
                    $from .= " {$j['type']} JOIN {$j['table']} {$j['alias']} ON {$j['on']}";
                }
            }

            // === CHECKBOX (distinct values) ===
            if ($kind === 'checkbox') {
                $expr = $f['expr'];
                $sql = "
                    SELECT DISTINCT {$expr} AS val
                    {$from}
                    {$baseWhere} AND {$expr} IS NOT NULL AND {$expr}::text <> ''
                    ORDER BY {$expr} ASC
                ";
                $vals = array_column($conn->fetchAllAssociative($sql, $params), 'val');
                $vals = array_map(static fn($v) => (string)$v, $vals);
                $assoc = !empty($vals) ? array_combine($vals, $vals) : [];
                $out[] = ['label' => $label, 'type' => $kind, 'key' => $key, 'values' => $assoc];
                continue;
            }

            // === RADIO (boolean → Yes/No) ===
            if ($kind === 'radio') {
                $expr = $f['expr'];
                $sql = "
                    SELECT DISTINCT {$expr} AS val
                    {$from}
                    {$baseWhere} AND {$expr} IS NOT NULL
                    ORDER BY {$expr} ASC
                ";
                $raw = array_column($conn->fetchAllAssociative($sql, $params), 'val');
                $labels = [];
                foreach ($raw as $v) {
                    // Normalize to Yes/No display
                    $labels[] = (filter_var($v, FILTER_VALIDATE_BOOLEAN) || $v === true || $v === 'true' || $v === 1 || $v === '1') ? 'Yes' : 'No';
                }
                $labels = array_values(array_unique($labels));
                // Stable order: Yes first, then No
                usort($labels, fn($a, $b) => ($a === 'Yes' ? 0 : 1) <=> ($b === 'Yes' ? 0 : 1));
                $out[] = ['label' => $label, 'type' => $kind, 'key' => $key, 'values' => $labels];
                continue;
            }

            // === RANGE (min/max + distinct ticks) ===
            if ($kind === 'range') {
                $expr = $f['expr'];
                $maxDistinct = $f['max_distinct'] ?? 50;

                $minMaxSql = "
                    SELECT MIN({$expr}) AS min, MAX({$expr}) AS max
                    {$from}
                    {$baseWhere} AND {$expr} IS NOT NULL
                ";
                $row = $conn->fetchAssociative($minMaxSql, $params) ?: ['min'=>null,'max'=>null];

                $ticksSql = "
                    SELECT DISTINCT {$expr} AS v
                    {$from}
                    {$baseWhere} AND {$expr} IS NOT NULL
                    ORDER BY {$expr} ASC
                    LIMIT {$maxDistinct}
                ";
                $ticks = array_column($conn->fetchAllAssociative($ticksSql, $params), 'v');
                $ticks = array_map(static fn($v) => (string)$v, $ticks);
                $distinctAssoc = !empty($ticks) ? array_combine($ticks, $ticks) : [];

                $out[] = [
                    'label'    => $label,
                    'key'      => $key,
                    'type'     => $kind,
                    'values'   => ['min' => $row['min'], 'max' => $row['max']],
                    'distinct' => $distinctAssoc,
                ];
                continue;
            }

            // === RANGE_SPAN (not used for CPU right now) ===
            if ($kind === 'range_span') {
                $minExpr = $f['min_expr'];
                $maxExpr = $f['max_expr'];
                $maxDistinct = $f['max_distinct'] ?? 50;

                $minMaxSql = "
                    SELECT MIN({$minExpr}) AS min, MAX({$maxExpr}) AS max
                    {$from}
                    {$baseWhere} AND {$minExpr} IS NOT NULL AND {$maxExpr} IS NOT NULL
                ";
                $row = $conn->fetchAssociative($minMaxSql, $params) ?: ['min'=>null,'max'=>null];

                $ticksSql = "
                    SELECT v FROM (
                        SELECT DISTINCT {$minExpr} AS v {$from} {$baseWhere} AND {$minExpr} IS NOT NULL
                        UNION
                        SELECT DISTINCT {$maxExpr} AS v {$from} {$baseWhere} AND {$maxExpr} IS NOT NULL
                    ) u
                    ORDER BY v ASC
                    LIMIT {$maxDistinct}
                ";
                $ticks = array_column($conn->fetchAllAssociative($ticksSql, $params), 'v');
                $ticks = array_map(static fn($v) => (string)$v, $ticks);
                $distinctAssoc = !empty($ticks) ? array_combine($ticks, $ticks) : [];

                $out[] = [
                    'label'    => $label,
                    'key'      => $key,
                    'type'     => $kind,
                    'values'   => ['min' => $row['min'], 'max' => $row['max']],
                    'distinct' => $distinctAssoc,
                ];
                continue;
            }

            throw new \LogicException("Unsupported filter kind '{$kind}' for '{$key}'");
        }

        return $out;
    }
}