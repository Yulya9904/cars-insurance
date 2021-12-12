<?php

declare(strict_types=1);

namespace Insurance\Controller;

use Application\Logger\LoggerDBService;
use Application\Logger\LogMessage;
use Application\Models\Entities\CarEntity;
use Insurance\Models\Entities\InsuranceEntity;
use Application\Models\Repositories\CarRepository;
use Application\Models\Repositories\DistrictRepository;
use Insurance\Forms\InsuranceForm;
use Application\Models\Entities\DistrictEntity;
use Application\Services\FileService;
use Insurance\Models\Repositories\InsuranceRepository;
use Laminas\Mvc\Controller\AbstractActionController;

class InsuranceController extends AbstractActionController
{
    protected $carRepository;
    protected $districtRepository;
    protected $insuranceRepository;
    protected $fileService;
    protected $loggerDB;

    public function __construct(
        CarRepository $carRepository,
        DistrictRepository $districtRepository,
        InsuranceRepository $insuranceRepository,
        FileService $fileService,
        LoggerDBService $loggerDB
    ) {
        $this->carRepository = $carRepository;
        $this->districtRepository = $districtRepository;
        $this->insuranceRepository = $insuranceRepository;
        $this->fileService = $fileService;
        $this->loggerDB = $loggerDB;
    }

    public function indexAction()
    {
        $car_id = (int) $this->params()->fromRoute('car_id', 0);
        $car = $car_id > 0 ? $this->carRepository->fetchCarById($car_id) : new CarEntity();
        if (empty($car->car_id) && $car_id != 0) {
            $this->flashMessenger()->addWarningMessage('Автомобиля с указанным идентификатором не найдено.');
            return $this->redirect()->toRoute('home');
        }
        $insurances = $this->insuranceRepository->fetchInsurances(empty($car->car_id) ? null : (int)$car->car_id);
        $this->fileService->fillFilesNamesForEachEntity($insurances);
        return ['insurances' => $insurances, 'car' => $car];
    }

    public function viewAction()
    {
        $insurance_id = (int) $this->params()->fromRoute('id', 0);
        if ($insurance_id > 0) {
            $insurance = $this->insuranceRepository->fetchInsuranceById($insurance_id);
            $this->fileService->fillFilesNames($insurance);
        }
        if (empty($insurance->insurance_id)) {
            $this->flashMessenger()->addWarningMessage('Страховки с указанным идентификатором не найдено.');
            return $this->redirect()->toRoute('home');
        }

        return ['insurance' => $insurance];
    }

    public function changeAction()
    {
        $insurance_id = (int) $this->params()->fromRoute('id', 0);
        $insurance = $insurance_id <= 0 ? new InsuranceEntity() : $this->insuranceRepository->fetchInsuranceById($insurance_id);
        $action  = empty($insurance->insurance_id) ? 'add' : 'edit';
        $form =  new InsuranceForm($this->carRepository);
        //создаем поле для загрузки изображений (для этого передаем путь для загрузки изображений), если изменение
        if ($action == "add") {
            $insurance->car_id = $this->params()->fromQuery('car_id', 0);
        }
        if ($action == "edit") {
            $this->fileService->fillFilesNames($insurance);
            $form->addImageField($insurance->folder_path);
            //убирем при редактировании поле с автомобилем
            $form->remove('car_id');
            $form->getInputFilter()->remove('car_id');
        }
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $action == 'add' ? $request->getPost() : array_merge_recursive($request->getFiles()->toArray(), $request->getPost()->toArray()); // в случае изменения информации сохраним на форме информацию о файлах
            $form->setData($data);
            if ($form->isValid()) {
                $data = $form->getData();
                $completed = false;
                if ($action == 'add') {
                    $insurance->exchangeArray($data);
                    $result = $this->insuranceRepository->addInsurance($insurance);
                    if (ctype_digit($result)) {
                        $insurance = $this->insuranceRepository->fetchInsuranceById((int) $result);
                        $completed = true;
                    }
                } else {
                    //выполняем перезапись свойств объекта, которые есть на форме
                    $old_values_insuranced_properties = [];
                    foreach ($data as $field => $value) {
                        if (property_exists($insurance, $field) && $value != $insurance->$field) {
                            $old_values_insuranced_properties[$field] = $insurance->$field; //формируем массив из старых значеных свойств, которые были изменены
                            $insurance->$field = $value;
                        }
                    }
                    //проверяем, что пользователь действительно что-то изменил, если изменения есть, то выполяем запись изменений в бд
                    if (!empty($old_values_insuranced_properties)) {
                        $result = $this->insuranceRepository->editInsurance($insurance);
                    }
                    $completed =  empty($result) ? TRUE : FALSE;
                    //формируем записи в лог для кажого изображения
                    foreach ($data['image'] as $image) {
                        if (empty($image['error'])) {
                            $logMessage = new LogMessage();
                            $logMessage->description = $insurance->getLogString('add_image', null, basename($image['tmp_name']));
                            $logMessage->author = $this->identity()->email;
                            $logMessage->record_id = $insurance->insurance_id;
                            $logMessage->action = 'insurance-add_image';
                            $this->loggerDB->addLogRecord($logMessage);
                        }
                    }
                }
                if ($completed) {
                    $logMessage = new LogMessage();
                    $logMessage->author = $this->identity()->email;
                    $logMessage->description = $insurance->getLogString($action,  $old_values_insuranced_properties ?? NULL);
                    $logMessage->record_id = $insurance->insurance_id;
                    $logMessage->action = 'insurance-' . $action;
                    $this->loggerDB->addLogRecord($logMessage);
                    $this->flashMessenger()->addSuccessMessage($action == 'add' ? 'Информация о новой страховке добавлена!' : 'Информация о страховке ТС успешно изменена!');
                    return $this->redirect()->toRoute('insurances', ['car_id' => $insurance->car_id]);
                } else {
                    $this->flashMessenger()->addErrorMessage($result);
                    return $this->redirect()->refresh();
                }
            }
        } else {
            $form->setData($insurance->getArrayCopy());
        }
        return ['form' => $form, 'insurance' => $insurance];
    }


    public function deleteAction()
    {
        $insurance_id = (int) $this->params()->fromRoute('id');
        $insurance = $this->insuranceRepository->fetchInsuranceById($insurance_id);
        $car = $this->carRepository->fetchCarById((int)$insurance->car_id);
        if (empty($insurance->insurance_id)) {
            $this->flashMessenger()->addWarningMessage('Страховки с указанным идентификатором не найдено.');
            return $this->redirect()->toRoute('home');
        }
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($request->getPost('del', 'Нет') == 'Да') {
                // инверсия статуса автомобиля 
                $result = $this->insuranceRepository->deleteInsurance($insurance_id);
                if ($result) {
                    $logMessage = new LogMessage();
                    $logMessage->description = $insurance->getLogString('delete');
                    $logMessage->author = $this->identity()->email;
                    $logMessage->record_id = $insurance_id;
                    $logMessage->action = 'insurance-delete';
                    $this->loggerDB->addLogRecord($logMessage);
                    $this->flashMessenger()->addSuccessMessage('Страховка автомобиля &laquo;' . $insurance->car_model . '&raquo; с гос.номером ' . $insurance->state_number . ' успешно удалена!');
                } else {
                    $this->flashMessenger()->addErrorMessage('Не удалось удалить страховку &laquo;' . $insurance->car_model . '&raquo; с гос.номером ' . $insurance->state_number . '!');
                }
            }
            return $this->redirect()->toRoute('insurances', ['car_id' => $insurance->car_id]);
        }

        return ['insurance' => $insurance, 'car' => $car];
    }
}
