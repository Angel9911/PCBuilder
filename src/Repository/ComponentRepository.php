<?php

namespace App\Repository;

use App\Entity\Component;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class ComponentRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

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

    public function findComponentById(int $id): Component
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find a component by name
     */
    public function findComponentByName(string $name): ?Component
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find components by type (e.g., CPU, GPU, RAM)
     */
    public function findComponentsByType(string $type): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.type', 'ct') // Assuming Component has a relation to ComponentType
            ->andWhere('ct.name = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getArrayResult();
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
        $cpu = isset($selected['cpu_id']) ? $this->getComponentSpecs('cpu', $connection, $selected['cpu_id']) : null;
        $gpu = isset($selected['gpu_id']) ? $this->getComponentSpecs('gpu', $connection, $selected['gpu_id']) : null;
        $monitor = isset($selected['monitor_id']) ? $this->getComponentSpecs('monitor', $connection, $selected['monitor_id']) : null;
        $motherboard = isset($selected['motherboard_id']) ? $this->getComponentSpecs('motherboard', $connection, $selected['motherboard_id']) : null;
        $case = isset($selected['pc_case_id']) ? $this->getComponentSpecs('pc_case', $connection, $selected['pc_case_id']) : null;
        $ram = isset($selected['ram_id']) ? $this->getComponentSpecs('ram', $connection, $selected['ram_id']) : null;
        $storage = isset($selected['storage_id']) ? $this->getComponentSpecs('storage', $connection, $selected['storage_id']) : null;
        $psu = isset($selected['psu_id']) ? $this->getComponentSpecs('psu', $connection, $selected['psu_id']) : null;
        //TODO: if choose first psu and then other component with power_wattage(cpu,gpu,monitor) it will not calculate the required power_wattage

        // Get compatible parts
        $response['cpu_ids'] = $this->getCompatibleCPUs($connection, $cpu, $motherboard, $ram, $psu, $gpu, $monitor);
        $response['motherboard_ids'] = $this->getCompatibleMotherboards($connection, $motherboard, $cpu, $ram, $storage, $case, $gpu);
        $response['ram_ids'] = $this->getCompatibleRAM($connection, $ram, $cpu, $motherboard);
        $response['gpu_ids'] = $this->getCompatibleGPUs($connection, $gpu, $motherboard, $case, $psu, $cpu, $monitor);
        $response['storage_ids'] = $this->getCompatibleStorage($connection, $storage, $motherboard);
        $response['psu_ids'] = $this->getCompatiblePSUs($connection, $cpu, $gpu, $monitor);
        $response['pc_case_ids'] = $this->getCompatibleCases($connection, $case, $motherboard, $gpu);
        $response['monitor_ids'] = $this->getAllMonitors($connection, $monitor, $psu, $cpu, $gpu);

        return $response;
    }

    /**
     * @throws Exception
     */
    function getComponentSpecs(string $type, Connection $conn = null, int $id = 0): ?array
    {

        // If no connection is passed, use default from service container
        if ($conn === null) {

            $conn = $this->entityManager->getConnection(); // Make sure this is injected in your service constructor
        }

        $sql = "
            SELECT t.*, comp.name, ct.name AS component_type
            FROM {$type} t
            JOIN components comp ON comp.id = t.component_id
            JOIN component_types ct ON ct.id = comp.type_id
        ";

        // Add WHERE clause only if ID is passed
        $params = [];

        if ($id > 0) {

            $sql .= " WHERE t.component_id = :id";
            $params['id'] = $id;
        }

        $stmt = $conn->prepare($sql);

        $result = $stmt->executeQuery($params);
        return $id > 0 ? ($result->fetchAssociative() ?: []) : $result->fetchAllAssociative();

    }

    function getAllMonitors($conn, ?array $selectedMonitor
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

            $results[] = ['component_id' => $selectedMonitor['component_id'], 'name' => $selectedMonitor['name'] ?? 'Selected Monitor'];
        }

        if($psu && $cpu && $gpu){

            $cpuPower = $cpu['power_wattage'] ?? 0;

            $gpuPower = $gpu['power_wattage'] ?? 0;

            // Max power allowed for GPU based on PSU minus other components and safety buffer
            $maxMonitorPower = $psu['power_wattage'] - ($cpuPower + $gpuPower + 100);

            // Prevent negative allowance
            $maxMonitorPower = max($maxMonitorPower, 0);

            $conditions[] = "m.power_wattage <= :max_monitor_power";
            $params['max_monitor_power'] = $maxMonitorPower;
        }

        $whereSql = '';

        if(!empty($conditions)){

            $whereSql = 'WHERE '. implode(" AND ", $conditions);
        }

        $sql = "SELECT m.component_id, comp.name 
                FROM monitor m 
                JOIN components comp ON comp.id = m.component_id
                 {$whereSql}
        ";

        $stmt = $conn->prepare($sql);

        $results = array_merge($results, $stmt->executeQuery($params)->fetchAllAssociative());

        return $results;
    }

    function getCompatibleCases($conn, ?array $selectedCase, ?array $mb, ?array $gpu): array {
        $conditions = []; // used for where clause in sql
        $params = []; // pass parameters for filtering

        $results = [];

        if ($selectedCase) {
            $conditions[] = "pc.component_id != :selected_id";
            $params['selected_id'] = $selectedCase['component_id'];

            $results[] = ['component_id' => $selectedCase['component_id'], 'name' => $selectedCase['name'] ?? 'Selected Case'];
        }

        /*if(!$mb && !$gpu) {

            return $results;
        }*/

        if($mb){

            $conditions[] = "EXISTS (
            SELECT 1
            FROM pc_case_form_factors cf         
            WHERE cf.pc_case_id = pc.id
                AND cf.form_factor_id = :form_factor_id
            )";

            $params['form_factor_id'] = $mb['form_factor_id'];
        }
        if($gpu){

            $conditions[] = "pc.gpu_clearance_mm >= :length_mm";
            $params['length_mm'] = $gpu['length_mm'];
        }

        $whereSql = '';

        if(!empty($conditions)){

            $whereSql = 'WHERE '. implode(" AND ", $conditions);
        }

        $sql = "
            SELECT pc.component_id, comp.name
            FROM pc_case pc
            JOIN components comp ON comp.id = pc.component_id
            {$whereSql}
        ";

        $stmt = $conn->prepare($sql);

        $results = array_merge($results, $stmt->executeQuery($params)->fetchAllAssociative());

        return $results;

    }

    function getCompatiblePSUs($conn, ?array $cpu, ?array $gpu, ?array $monitor): array {
        $requiredPower =
            ($cpu['power_wattage'] ?? 0) +
            ($gpu['power_wattage'] ?? 0) +
            ($monitor['power_wattage'] ?? 0) + 100;

        $sql = "
        SELECT psu.component_id, comp.name
        FROM psu
        JOIN components comp ON comp.id = psu.component_id
        WHERE psu.power_wattage >= :required
    ";

        $stmt = $conn->prepare($sql);

        return $stmt->executeQuery(['required' => $requiredPower])->fetchAllAssociative();
    }

    function getCompatibleStorage($conn, ?array $selectedStorage, ?array $mb): array {
        $conditions = []; // used for where clause in sql
        $params = []; // pass parameters for filtering

        $results = [];

        if($selectedStorage){
            $conditions[] = "s.component_id != :selected_id";
            $params['selected_id'] = $selectedStorage['component_id'];

            $results[] = ['component_id' => $selectedStorage['component_id'], 'name' => $selectedStorage['name'] ?? 'Selected Storage'];
        }

        if($mb){

            $storageInterfaces = str_getcsv(trim($mb['storage_interfaces'], '{}'));

            if(!empty($storageInterfaces)){

                $conditions[] = "s.interface = ANY(:interfaces::text[])";
                $params['interfaces'] = '{' . implode(',', $storageInterfaces) . '}';
            }
        }

        $whereSql = '';

        if(!empty($conditions)){

            $whereSql = 'WHERE '. implode(" AND ", $conditions);
        }

        $sql = "
            SELECT s.component_id, comp.name
            FROM storage s
            JOIN components comp ON comp.id = s.component_id
            {$whereSql}
        ";

        $stmt = $conn->prepare($sql);

        $results = array_merge($results, $stmt->executeQuery($params)->fetchAllAssociative());

        return $results;
    }

    function getCompatibleGPUs( $conn
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

        if ($selectedGpu) {
            $conditions[] = "gpu.component_id != :selected_id";
            $params['selected_id'] = $selectedGpu['component_id'];

            $results[] = ['component_id' => $selectedGpu['component_id'], 'name' => $selectedGpu['name'] ?? 'Selected GPU'];
        }

        if ($mb) {

            $conditions[] = "gpu.pcie_version <= :pcie_version";
            $params['pcie_version'] = $mb['pcie_version'];
        }

        if ($case) {

            $conditions[] = "gpu.length_mm <= :gpu_clearance";
            $params['gpu_clearance'] = $case['gpu_clearance_mm'];
        }

        if($psu && $cpu && $monitor){

            $cpuPower = $cpu['power_wattage'] ?? 0;

            $monitorPower = $monitor['power_wattage'] ?? 0;

            // Max power allowed for GPU based on PSU minus other components and safety buffer
            $maxGpuPower = $psu['power_wattage'] - ($cpuPower + $monitorPower + 100);

            // Prevent negative allowance
            $maxGpuPower = max($maxGpuPower, 0);

            $conditions[] = "gpu.power_wattage <= :max_gpu_power";

            $params['max_cpu_power'] = $maxGpuPower;
        }

        $whereSql = '';

        if(!empty($conditions)){

            $whereSql = 'WHERE '. implode(" AND ", $conditions);
        }

        $sql = "
        SELECT gpu.component_id, comp.name
        FROM gpu
        JOIN components comp ON comp.id = gpu.component_id
        {$whereSql}
        ";

        $stmt = $conn->prepare($sql);

        $results = array_merge($results, $stmt->executeQuery($params)->fetchAllAssociative());

        return $results;
    }

    function getCompatibleRAM( $conn, ?array $selectedRam, ?array $cpu, ?array $mb): array {
        $conditions = []; // used for where clause in sql
        $params = []; // pass parameters for filtering

        $results = [];

        if ($selectedRam) {
            $conditions[] = "ram.component_id != :selected_id";
            $params['selected_id'] = $selectedRam['component_id'];

            // if only ram is passed
            $results[] = ['component_id' => $selectedRam['component_id'], 'name' => $selectedRam['name'] ?? 'Selected RAM'];
        }

        /*if(!$cpu && !$mb){

            //motherboard and cpu not passed
            return $results;
        }*/

        if ($cpu) {

            $conditions[] = "ram.type = :cpu_type";
            $params['cpu_type'] = $cpu['memory_type'];
        }

        if ($mb) {

            $conditions[] = "ram.type = :mb_type";
            $params['mb_type'] = $mb['memory_type'];

            $conditions[] = "ram.modules <= :slots";
            $params['slots'] = $mb['memory_slots'];

            $conditions[] = "ram.capacity_gb <= :max_capacity";
            $params['max_capacity'] = $mb['max_memory_supported'];

            $motherboardSupportedSpeed = str_getcsv(trim($mb['supported_memory_speeds'], '{}'));

            if(!empty($motherboardSupportedSpeed)){

                $conditions[] = "ram.speed_mhz = ANY(:supported_speeds::integer[])";
                $params['supported_speeds'] = '{' . implode(',', $motherboardSupportedSpeed) . '}';
            }

        }

        $whereSql = '';

        if(!empty($conditions)){

            $whereSql = 'WHERE '. implode(" AND ", $conditions);
        }

        $sql = "
        SELECT ram.component_id, comp.name
        FROM ram
        JOIN components comp ON comp.id = ram.component_id
        {$whereSql}
    ";

        $stmt = $conn->prepare($sql);

        $results = array_merge($results, $stmt->executeQuery($params)->fetchAllAssociative());

        return $results;
    }

    function getCompatibleMotherboards(
            $conn,
            ?array $selectedMb,
            ?array $cpu,
            ?array $ram,
            ?array $storage,
            ?array $pcCase,
            ?array $gpu
    ): array {
        $conditions = []; // used for where clause in sql
        $params = []; // pass parameters for filtering

        $results = [];

        if ($selectedMb) {
            $conditions[] = "mb.component_id != :selected_id";
            $params['selected_id'] = $selectedMb['component_id'];

            $results[] = ['component_id' => $selectedMb['component_id'], 'name' => $selectedMb['name'] ?? 'Selected Motherboard'];
        }

        /*if(!$cpu && !$ram && !$storage && !$pcCase && !$gpu){

            return $results;
        }*/

        if($cpu){

            $conditions[] = "mb.socket = :socket";
            $params['socket'] = $cpu['socket'];

            $cpuChipsets= str_getcsv(trim($cpu['chipset'], '{}'));

            if(!empty($cpuChipsets)){

                $conditions[] = "mb.chipset = ANY(:cpu_chipsets::text[])"; // but here cpu chipset is array text[]

                $params['cpu_chipsets'] = '{' . implode(',', $cpuChipsets) . '}';
            }
        }

        if($ram){

            $conditions[] = "mb.memory_type = :memory_type";
            $params['memory_type'] = $ram['type'];

            $conditions[] = "mb.memory_slots >= :modules";
            $params['modules'] = $ram['modules'];

            $conditions[] = "mb.max_memory_supported >= :capacity_gb";
            $params['capacity_gb'] = $ram['capacity_gb'];

            $conditions[] = ":speed_mhz = ANY(mb.supported_memory_speeds)";
            $params['speed_mhz'] = $ram['speed_mhz'];
        }

        if($storage){

            $conditions[] = ":interface = ANY(mb.storage_interfaces)";
            $params['interface'] = $storage['interface'];
        }

        // PC Case compatibility (Motherboard form factor must be supported by the PC case)
        if ($pcCase) {

            $conditions[] = "EXISTS (
            SELECT 1
            FROM pc_case_form_factors cf
            WHERE cf.pc_case_id = :case_id
              AND cf.form_factor_id = mb.form_factor_id
            )";

            $params['case_id'] = $pcCase['component_id']; // assuming component_id is pc_case_id
        }

        if($gpu){

            $conditions[] = "mb.pcie_version >= :gpu_pcie_version";
            $params['gpu_pcie_version'] = $gpu['pcie_version'];
        }

        $whereSql = '';

        if(!empty($conditions)){

            $whereSql = 'WHERE '. implode(" AND ", $conditions);
        }

        $sql = "
            SELECT mb.component_id, comp.name
            FROM motherboard mb
            JOIN components comp ON comp.id = mb.component_id
            {$whereSql}
        ";

        //var_dump($sql);

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

        if ($selectedCpu) {
            $conditions[] = "cpu.component_id != :selected_id";
            $params['selected_id'] = $selectedCpu['component_id'];

            $results[] = ['component_id' => $selectedCpu['component_id'], 'name' => $selectedCpu['name'] ?? 'Selected CPU'];
        }

        /*if(!$motherboard && !$ram){

            return $results;
        }*/

        if($motherboard){

            $conditions[] = "cpu.socket = :socket";
            $params['socket'] = $motherboard['socket'];

            $conditions[] = ":chipset = ANY(cpu.chipset)";
            $params['chipset'] =  $motherboard['chipset'];
        }

        if($ram){
            $conditions[] = "cpu.memory_type = :type";
            $params['type'] = $ram['type'];
        }

        if($psu && $gpu && $monitor){

            $gpuPower = $gpu['power_wattage'] ?? 0;

            $monitorPower = $monitor['power_wattage'] ?? 0;

            // Max power allowed for CPU based on PSU minus other components and safety buffer
            $maxCpuPower = $psu['power_wattage'] - ($gpuPower + $monitorPower + 100);

            // Prevent negative allowance
            $maxCpuPower = max($maxCpuPower, 0);

            $conditions[] = "cpu.power_wattage <= :max_cpu_power";
            $params['max_cpu_power'] = $maxCpuPower;
        }

        $whereSql = '';

        if(!empty($conditions)){

            $whereSql = 'WHERE '. implode(" AND ", $conditions);
        }

        // Build final query
        $sql = "
        SELECT cpu.component_id, comp.name
        FROM cpu
        JOIN components comp ON comp.id = cpu.component_id
        {$whereSql}
        ";

        $stmt = $conn->prepare($sql);

        $results = array_merge($results, $stmt->executeQuery($params)->fetchAllAssociative());

        return $results;
    }

    private function calculateRequiredPsuPower(?array $cpu, ?array $gpu, ?array $monitor): int {
        return
            ($cpu['power_wattage'] ?? 0) +
            ($gpu['power_wattage'] ?? 0) +
            ($monitor['power_wattage'] ?? 0) +
            100; // buffer
    }
}