<?php

namespace App\Repository;

use App\Entity\Component;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
     * Used when the user doesn't pass any parameter to compatible method.
     * @param string $type
     * @return array
     */
    public function findComponentsByTypeMapped(string $type): array
    {
        $results = $this->createQueryBuilder('c')
            ->select('c.id', 'c.name')
            ->innerJoin('c.type', 'ct')
            ->andWhere('ct.name = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getArrayResult();

        $mapped = [];
        foreach ($results as $row) {
            $mapped[$row['id']] = [$row['name']];
        }

        return $mapped;
    }

    /**
     * @param array $componentParams
     * @return array[]
     * @throws Exception
     */
    public function findCompatibleComponents(array $componentParams = []): array
    {
        $connection = $this->entityManager->getConnection();

        // if the given component parameters are empty, return the default values
        if(empty($componentParams)){

            return [
                'cpu_ids' => $this->findComponentsByTypeMapped('cpu'),
                'motherboard_ids' => $this->findComponentsByTypeMapped('motherboard'),
                'psu_ids' => $this->findComponentsByTypeMapped('psu'),
                'gpu_ids' => $this->findComponentsByTypeMapped('gpu'),
                'ram_ids' => $this->findComponentsByTypeMapped('ram'),
                'storage_ids' => $this->findComponentsByTypeMapped('storage'),
                'pc_case_ids' => $this->findComponentsByTypeMapped('pc_case'),
                'monitor_ids' => $this->findComponentsByTypeMapped('monitor'),
            ];
        }

        $sqlQuery =
    "
            WITH selected_components AS 
        (
        SELECT 
            comp.id AS component_id, 
            mb.component_id AS motherboard_id, mb.socket AS mb_socket, mb.chipset AS mb_chipset, mb.memory_type AS mb_memory_type, mb.storage_interfaces,
            cpu.component_id AS cpu_id, cpu.socket AS cpu_socket, cpu.chipset AS cpu_chipset, cpu.memory_type AS cpu_memory_type, cpu.power_wattage AS cpu_power,
            ram.component_id AS ram_id, ram.type AS ram_type,
            storage.component_id AS storage_id, storage.interface AS storage_interface,
            psu.component_id AS psu_id, psu.power_wattage AS psu_power,
            gpu.component_id AS gpu_id, gpu.power_wattage AS gpu_power,
            pc_case.component_id AS pc_case_id, pc_case.gpu_clearance_mm AS gpu_length_mm,
            monitor.component_id AS monitor_id, monitor.power_wattage AS monitor_power
        FROM components comp
        LEFT JOIN motherboard mb ON comp.id = mb.component_id
        LEFT JOIN cpu ON comp.id = cpu.component_id
        LEFT JOIN ram ON comp.id = ram.component_id
        LEFT JOIN storage ON comp.id = storage.component_id
        LEFT JOIN psu ON comp.id = psu.component_id
        LEFT JOIN gpu ON comp.id = gpu.component_id
        LEFT JOIN pc_case ON comp.id = pc_case.component_id
        LEFT JOIN monitor ON comp.id = monitor.component_id
        )
    SELECT DISTINCT
        mb.component_id AS motherboard_id, comp_mb.name as motherboard_name,
        cpu.component_id AS cpu_id, comp_cpu.name as cpu_name,
        ram.component_id AS ram_id, comp_ram.name as ram_name,
        storage.component_id AS storage_id, comp_storage.name as storage_name,
        psu.component_id AS psu_id, comp_psu.name as psu_name,
        gpu.component_id AS gpu_id, comp_gpu.name as gpu_name,
        pc_case.component_id AS pc_case_id, comp_case.name as case_name,
        monitor.component_id AS monitor_id, comp_monitor.name as monitor_name,
        FROM selected_components sc

    --  CPU-Motherboard Compatibility (matching socket AND chipset)
    LEFT JOIN motherboard mb ON sc.motherboard_id = mb.component_id
    LEFT JOIN components comp_mb ON mb.component_id = comp_mb.id
    LEFT JOIN cpu ON cpu.socket = mb.socket AND mb.chipset = ANY(cpu.chipset)
    LEFT JOIN components comp_cpu ON cpu.component_id = comp_cpu.id
    
    --  RAM-Motherboard Compatibility (should match motherboard memory_type/modules/max_memory_supported with RAM memory type/modules/capacity_gb)
    --  RAM-CPU Compatability (should match CPU memory type with RAM memory_type)
    LEFT JOIN ram ON ram.type = mb.memory_type 
        AND ram.type = cpu.memory_type 
        AND ram.modules <= mb.memory_slots
        AND ram.capacity_gb <= mb.max_memory_supported
    LEFT JOIN components comp_ram ON ram.component_id = comp_ram.id
    
    --  Storage Compatibility (should match motherboard's supported interfaces)
    LEFT JOIN storage ON storage.interface = ANY(mb.storage_interfaces)
    LEFT JOIN components comp_storage ON storage.component_id = comp_storage.id
    
    -- PC Case Compatibility (should match pc case form factor with motherboard form factor)
    LEFT JOIN pc_case ON TRUE
    LEFT JOIN pc_case_form_factors cf ON pc_case.id = cf.pc_case_id AND cf.form_factor_id = mb.form_factor_id
    LEFT JOIN components comp_case ON pc_case.component_id = comp_case.id
    
    --  GPU Compatibility (should match motherboard PCIe AND should fit in pc case)
    LEFT JOIN gpu ON gpu.pcie_version <= mb.pcie_version AND gpu.length_mm <= pc_case.gpu_clearance_mm 
    LEFT JOIN components comp_gpu ON gpu.component_id = comp_gpu.id
    
    -- Monitor Compatibility(now it's used only for PSU power_wattage) 
    LEFT JOIN monitor ON true
    LEFT JOIN components comp_monitor ON monitor.component_id = comp_monitor.id
    
    -- PSU Compatibility (must provide enough power for CPU + GPU + Monitor + 100 in advance)
    LEFT JOIN psu ON psu.power_wattage >= (COALESCE(cpu.power_wattage, 0) + COALESCE(gpu.power_wattage, 0) + COALESCE(monitor.power_wattage, 0) + 100)
    LEFT JOIN components comp_psu ON psu.component_id = comp_psu.id
    
    --  Apply dynamic filtering for selected components
    WHERE 1=1
    ";

        // Dynamically add filters based on provided parameters
        $parameters = [];
        if (!empty($componentParams['cpu_id'])) {
            $sqlQuery .= " AND cpu.component_id = :cpu_id";
            $parameters['cpu_id'] = $componentParams['cpu_id'];
        }
        if (!empty($componentParams['motherboard_id'])) {
            $sqlQuery .= " AND mb.component_id = :motherboard_id";
            $parameters['motherboard_id'] = $componentParams['motherboard_id'];
        }
        if (!empty($componentParams['ram_id'])) {
            $sqlQuery .= " AND ram.component_id = :ram_id";
            $parameters['ram_id'] = $componentParams['ram_id'];
        }
        if (!empty($componentParams['storage_id'])) {
            $sqlQuery .= " AND storage.component_id = :storage_id";
            $parameters['storage_id'] = $componentParams['storage_id'];
        }
        if (!empty($componentParams['psu_id'])) {
            $sqlQuery .= " AND psu.component_id = :psu_id";
            $parameters['psu_id'] = $componentParams['psu_id'];
        }
        if (!empty($componentParams['gpu_id'])) {
            $sqlQuery .= " AND gpu.component_id = :gpu_id";
            $parameters['gpu_id'] = $componentParams['gpu_id'];
        }
        if (!empty($componentParams['pc_case_id'])) {
            $sqlQuery .= " AND pc_case.component_id = :pc_case_id";
            $parameters['pc_case_id'] = $componentParams['pc_case_id'];
        }
        if (!empty($componentParams['monitor_id'])) {
            $sqlQuery .= " AND monitor.component_id = :monitor_id";
            $parameters['monitor_id'] = $componentParams['monitor_id'];
        }

        // Execute query
        $stmt = $connection->prepare($sqlQuery);
        $resultData = $stmt->executeQuery($parameters);

        $resultAsArray = $resultData->fetchAllAssociative();

    // Filter out any items where all component IDs are null
        $filteredResult = array_filter($resultAsArray, function ($element) {


            return $element['motherboard_id'] != null ||
                $element['cpu_id'] != null ||
                $element['ram_id'] != null ||
                $element['storage_id'] != null ||
                $element['psu_id'] != null ||
                $element['gpu_id'] != null ||
                $element['pc_case_id'] != null ||
                $element['monitor_id'] != null;
        });

        // Define arrays for each component type
        $motherboard_ids = [];
        $cpu_ids = [];
        $ram_ids = [];
        $storage_ids = [];
        $psu_ids = [];
        $gpu_ids = [];
        $pc_case_ids = [];
        $monitor_ids = [];

        // Loop through each row and collect unique values
        foreach ($filteredResult as $row) {

            if (!is_null($row['motherboard_id']) && !is_null($row['motherboard_name'])){

                $motherboard_ids[$row['motherboard_id']][] = $row['motherboard_name'];
            }
            if (!is_null($row['cpu_id']) && !is_null($row['cpu_name'])){

                $cpu_ids[$row['cpu_id']][] = $row['cpu_name'];
            }
            if (!is_null($row['ram_id']) && !is_null($row['ram_name'])){

                $ram_ids[$row['ram_id']][] = $row['ram_name'];
            }
            if (!is_null($row['storage_id']) && !is_null($row['storage_name'])){

                $storage_ids[$row['storage_id']][] = $row['storage_name'];
            }
            if (!is_null($row['psu_id']) && !is_null($row['psu_name'])){

                $psu_ids[$row['psu_id']][] = $row['psu_name'];
            }
            if (!is_null($row['gpu_id']) && !is_null($row['gpu_name'])){

                $gpu_ids[$row['gpu_id']][] = $row['gpu_name'];
            }
            if (!is_null($row['pc_case_id']) && !is_null($row['case_name'])){

                $pc_case_ids[$row['pc_case_id']][] = $row['case_name'];
            }
            if (!is_null($row['monitor_id']) && !is_null($row['monitor_name'])){

                $pc_case_ids[$row['monitor_id']][] = $row['monitor_name'];
            }
        }

        $motherboards = $this->getUniqueComponents($motherboard_ids);
        $cpus= $this->getUniqueComponents($cpu_ids);
        $rams= $this->getUniqueComponents($ram_ids);
        $storages = $this->getUniqueComponents($storage_ids);
        $psus = $this->getUniqueComponents($psu_ids);
        $gpus = $this->getUniqueComponents($gpu_ids);
        $pcCases = $this->getUniqueComponents($pc_case_ids);
        $monitors = $this->getUniqueComponents($monitor_ids);

        // Final structured output
        $result = [
            'motherboard_ids' => $motherboards,
            'cpu_ids' => $cpus,
            'ram_ids' => $rams,
            'storage_ids' => $storages,
            'psu_ids' => $psus,
            'gpu_ids' => $gpus,
            'ps_case_ids' => $pcCases,
            'monitor_ids' => $monitors,
        ];

        // Print the result
        return $result;
    }

    /**
     * Find components within a power consumption range
     */
    public function findComponentsByPowerRange(int $minWatt, int $maxWatt): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.powerWattage BETWEEN :minWatt AND :maxWatt')
            ->setParameter('minWatt', $minWatt)
            ->setParameter('maxWatt', $maxWatt)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array $components
     * @return array
     */
    public function getUniqueComponents(array $components): array
    {
        foreach ($components as $id => $names) {
            $components[$id] = array_values(array_unique($names));
        }
        return $components;
    }

}