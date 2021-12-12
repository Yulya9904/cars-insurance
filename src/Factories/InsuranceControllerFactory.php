<?php

namespace Insurance\Factories;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Application\Models\Repositories\CarRepository;
use Application\Models\Repositories\DistrictRepository;
use Application\Services\FileService;
use Application\Logger\LoggerDBService;
use Insurance\Controller\InsuranceController;
use Insurance\Models\Repositories\InsuranceRepository;

class InsuranceControllerFactory implements FactoryInterface
{
       public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
       {
              $carRepository = $container->get(CarRepository::class);
              $districtRepository = $container->get(DistrictRepository::class);
              $insuranceRepository = $container->get(InsuranceRepository::class);
              $fileService = $container->get(FileService::class);
              $loggerDB = $container->get(LoggerDBService::class);
              return new InsuranceController($carRepository, $districtRepository, $insuranceRepository, $fileService, $loggerDB);
       }
}
