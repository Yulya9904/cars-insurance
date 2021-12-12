<?php

namespace Insurance\Models\Entities;

class InsuranceEntity
{
    public $insurance_id;
    public $district_id;
    public $district_name;
    public $car_id;
    public $car_model;
    public $state_number;
    public $insurer_name;
    public $insurance_start_date;
    public $insurance_end_date;
    public $cost;


    public $folder_path;
    public $file_names;

    protected $field_alias = [
        'district_name'           => ['add' => 'район',                             'edit' => 'о райоре'],
        'insurer_name'            => ['add' => 'страховой компании',                'edit' => 'о страховой компании'],
        'insurance_start_date'    => ['add' => 'дата начала действия',              'edit' => 'о дате начала действия'],
        'insurance_end_date'      => ['add' => 'дата окончания действия',           'edit' => 'о дате окончания действия'],
        'cost'                    => ['add' => 'стоимость страховки',               'edit' => 'о стоимости страховки'],
    ];



    public function exchangeArray(array $data)
    {
        $this->insurance_id            =  empty($data['insurance_id'])           ?  null    :    $data['insurance_id'];
        $this->district_id             =  empty($data['district_id'])            ?  null    :    $data['district_id'];
        $this->district_name           =  empty($data['district_name'])          ?  null    :    $data['district_name'];
        $this->car_id                  =  empty($data['car_id'])                 ?  null    :    $data['car_id'];
        $this->car_model               =  empty($data['car_model'])              ?  null    :    $data['car_model'];
        $this->state_number            =  empty($data['state_number'])           ?  null    :    $data['state_number'];
        $this->insurer_name            =  empty($data['insurer_name'])           ?  null    :    $data['insurer_name'];
        $this->insurance_start_date    =  empty($data['insurance_start_date'])   ?  null    :    $data['insurance_start_date'];
        $this->insurance_end_date      =  empty($data['insurance_end_date'])     ?  null    :    $data['insurance_end_date'];
        $this->cost                    =  empty($data['cost'])                   ?  null    :    $data['cost'];
    }

    /**
     * getClassNameAbbreviation  получим сокращенное название сущности для формирования маршрута изображения
     *
     * @return string
     */
    public function getClassNameAbbreviation(): string
    {
        return 'insurance';
    }

    /**
     * getColor определим цвет страховки в зависимости от даты истечения
     *
     * @return void
     */
    public function getColor()
    {
        if ($this->insurance_end_date > date('Y-m-d', strtotime('+1 month'))) {
            $this->color = 'good';
        } elseif ($this->insurance_end_date > date('Y-m-d', strtotime('+14 day'))) {
            $this->color = 'average';
        } else {
            $this->color = 'bad';
        }
    }


    //При использовании гидратора используется для поддержки извлечения. Также используется при изменении данных в repository
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }


    public function getLogString(string $action, ?array $oldGarage = [], ?string $image_name = null)
    {
        $log_text = '';
        if ($action == 'add') {
            $log_text = 'добавлена страховка №' . $this->insurance_id . ' на ТС ' . $this->car_model . ' "' . $this->state_number . '"';
            $log_text .= ' (';
            foreach ($this as $key => $value) {
                $log_text .= (empty($this->$key) || empty($this->field_alias[$key])) ? '' : ' ' . $this->field_alias[$key]['add'] . ' - ' . $this->$key . ',';
            }
            $log_text =  trim($log_text, ',');
            $log_text .= '). ';
        }

        if ($action == 'edit') {
            $log_text = 'изменена информация о страховке №' . $this->insurance_id . ' на ТС ' . $this->car_model . ' "' . $this->state_number . '"';
            foreach ($oldGarage as $key => $value) {
                $log_text .= (empty($this->field_alias[$key]) ? '' : ' ' . $this->field_alias[$key]['edit']) . ' с "' . (empty($value) ? '' : $value) . '" на "' . (empty($this->$key) ? '' : $this->$key) . '",';
            }
            $log_text =  trim($log_text, ',');
        }

        if ($action == 'delete') {
            $log_text = 'удалена страховка №' . $this->insurance_id . ' на ТС ' . $this->car_model . ' "' . $this->state_number . '"';
            $log_text .= ' (';
            foreach ($this as $key => $value) {
                $log_text .= (empty($this->$key) || empty($this->field_alias[$key])) ? '' : ' ' . $this->field_alias[$key]['add'] . ' - ' . $this->$key . ',';
            }
            $log_text =  trim($log_text, ',');
            $log_text .= '). ';
        }

        if ($action == 'add_image') {
            $log_text = 'добавлено изображение ' . $image_name . ' для страховки №' . $this->insurance_id . ' на ТС ' . $this->car_model . ' "' . $this->state_number . '"';
        }

        if ($action == 'delete_image') {
            $log_text = 'удалено изображение ' . $image_name;
        }
        return $log_text;
    }
}
