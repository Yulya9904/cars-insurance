<?php

namespace Insurance\Models\Repositories;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Insurance\Models\Entities\InsuranceEntity;
use Laminas\Db\Sql\Expression;

class InsuranceRepository extends AbstractTableGateway
{
    protected $adapter;
    protected $author;
    protected $table = 'insurances';
    const INSURANCE_TABLE_ROWS = ['car_id', 'insurer_name', 'insurance_start_date', 'insurance_end_date', 'cost'];

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->initialize();
    }

    /**
     * fetchInsurances
     *
     * @param  int|null $car_id
     * @return array
     */
    public function fetchInsurances(?int $car_id = null): array
    {
        $sqlQuery = $this->sql->select()
            ->join('cars', $this->table . '.car_id=cars.car_id', ['state_number', 'car_model'])
            ->join('districts', 'cars.home_district_id=districts.district_id', ['district_name']);
        if ($car_id) {
            $sqlQuery->where([$this->table . '.car_id' => $car_id]);
        }
        $sqlQuery->order('insurance_end_date DESC, district_name ASC');
        $sqlStmt = $this->sql->prepareStatementForSqlObject($sqlQuery);
        $results = $sqlStmt->execute();
        $insuranceEntities = [];
        if ($results->count() > 0) {
            foreach ($results as $insuranceAsArray) {
                $insuranceEntity = new InsuranceEntity();
                $insuranceEntity->exchangeArray($insuranceAsArray);
                $insuranceEntities[] = $insuranceEntity;
            }
        }
        return $insuranceEntities;
    }

    /**
     * fetchActiveInsurances
     *
     * @param  int $car_id
     * @return Application\Models\Entities\InsuranceEntity
     */
    public function fetchActiveInsurance(int $car_id): InsuranceEntity
    {
        $sqlQuery = $this->sql->select();
        $sqlQuery->join('cars', $this->table . '.car_id=cars.car_id', ['state_number', 'car_model'])
            ->where('insurance_end_date >= CURRENT_DATE() AND insurance_start_date <= CURRENT_DATE() AND ' . $this->table . '.car_id = ' . $car_id);
        $sqlStmt = $this->sql->prepareStatementForSqlObject($sqlQuery);
        $result = $sqlStmt->execute()->current();
        $insuranceEntity = new InsuranceEntity();
        if ($result) {
            $insuranceEntity->exchangeArray($result);
        }
        return $insuranceEntity;
    }


    /**
     * fetchInsuranceById
     *
     * @param  int $insurance_id
     * @return InsuranceEntity
     */
    public function fetchInsuranceById(int $insurance_id): ?InsuranceEntity
    {
        $insurance_id = (int)$insurance_id;
        $sqlQuery = $this->sql->select()
            ->join('cars', $this->table . '.car_id=cars.car_id', ['state_number', 'car_model'])
            ->join('districts', 'cars.home_district_id=districts.district_id', ['district_name'])
            ->where(['insurance_id' => $insurance_id]);
        $sqlStmt = $this->sql->prepareStatementForSqlObject($sqlQuery);
        $result = $sqlStmt->execute()->current();
        $insuranceEntity = new InsuranceEntity();
        if ($result) {
            $insuranceEntity->exchangeArray($result);
        }
        return $insuranceEntity;
    }

    /**
     * addInsurance
     *
     * @param  InsuranceEntity $request
     * @return string id новой записи | текст ошибки при неудачной попытке добавления
     */
    public function addInsurance(InsuranceEntity $insurance): string
    {
        $connection = $this->adapter->getDriver()->getConnection();
        $connection->beginTransaction();
        try {
            if (!$this->checkValidInsuranceDate($insurance)) {
                $connection->rollback();
                return 'У данного автомобиля была действующая страховка в указанный период.';
            };
            $params = [];
            foreach ($insurance as $key => $value) {
                if (in_array($key, self::INSURANCE_TABLE_ROWS)) {
                    $params[$key] = $value ?? null;
                }
            }
            $sqlQuery = $this->sql->insert()->values(['created_dt' => date('Y-m-d H:i:s'),] + $params);
            $sqlStmt  = $this->sql->prepareStatementForSqlObject($sqlQuery);
            $result =   $sqlStmt->execute();
            $connection->commit();
            return $result->getGeneratedValue();
        } catch (\Exception $e) {
            $connection->rollback();
            return "Не удалось добавить страховку! Повторите попытку позже!";
        }
    }

    /**
     * editInsurance
     *
     * @param  InsuranceEntity $insuranceEntity
     * @return string|null текст ошибки | null при удачном изменении
     */
    public function editInsurance(InsuranceEntity $insurance): ?string
    {
        $connection = $this->adapter->getDriver()->getConnection();
        $connection->beginTransaction();
        try {
            if (!$this->checkValidInsuranceDate($insurance)) {
                $connection->rollback();
                return 'У данного автомобиля была действующая страховка в указанный период.';
            };
            $params = [];
            foreach ($insurance as $key => $value) {
                if (in_array($key, self::INSURANCE_TABLE_ROWS)) {
                    $params[$key] = $value ?? null;
                }
            }
            $sqlQuery = $this->sql->update()->set($params)->where(['insurance_id' => $insurance->insurance_id]);
            $sqlStmt = $this->sql->prepareStatementForSqlObject($sqlQuery);
            $sqlStmt->execute();
            $connection->commit();
            return null;
        } catch (\Exception $e) {
            $connection->rollback();
            return "Не удалось изменить информацию о страховке! Повторите попытку позже!";
        }
    }

    /**
     * deleteInsurance
     *
     * @param  int $insurance_id
     * @return bool
     */
    public function deleteInsurance(int $insurance_id): bool
    {
        $sqlQuery = $this->sql->delete()->where(['insurance_id' => $insurance_id]);
        $sqlStmt = $this->sql->prepareStatementForSqlObject($sqlQuery);
        $results = $sqlStmt->execute();
        if ($results) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * checkValidInsuranceDate
     *
     * @param  InsuranceEntity $car_id
     * @return bool
     */
    public function checkValidInsuranceDate(InsuranceEntity $insurance): bool
    {
        $sqlQuery = $this->sql->select()
            ->join('cars', $this->table . '.car_id=cars.car_id', ['state_number', 'car_model'])
            ->join('districts', 'cars.home_district_id=districts.district_id', ['district_name']);
        $sqlQuery->where('insurance_id!=' . (empty($insurance->insurance_id) ? 0 : $insurance->insurance_id) . ' and cars.car_id=' . $insurance->car_id . ' and (
        (insurance_start_date>=DATE("' . $insurance->insurance_start_date . '") and insurance_start_date<DATE("' . $insurance->insurance_end_date . '") or
        insurance_end_date>DATE("' . $insurance->insurance_start_date . '") and insurance_end_date<=DATE("' . $insurance->insurance_end_date . '")) or
        (insurance_start_date<=DATE("' . $insurance->insurance_start_date . '") and insurance_end_date>=DATE("' . $insurance->insurance_end_date . '")))');

        $sqlStmt = $this->sql->prepareStatementForSqlObject($sqlQuery);
        $results = $sqlStmt->execute();
        if ($results->count() > 0) {
            return false;
        } else {
            return true;
        }
    }
}
