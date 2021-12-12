<?php

declare(strict_types=1);

namespace Insurance\Forms;

use Application\Models\Repositories\CarRepository;
use Laminas\InputFilter;
use Laminas\Form\Element;
use Laminas\Form\Form;
use Laminas\Filter;
use Laminas\Validator;
use Laminas\I18n;



class InsuranceForm extends Form
{
    protected $carRepository;

    public function __construct(CarRepository $carRepository)
    {
        $this->carRepository = $carRepository;
        parent::__construct('insuranceForm');
        $this->setAttributes(['method' => 'post', 'class' => 'd-flex flex-column mb-3']);

        $this->add([
            'type' => Element\Select::class,
            'name' => 'car_id',
            'options' => [
                'label' => 'Автомобиль',
                'label_attributes' => [
                    'class' => 'my-1 text-dark font-weight-bold',
                ],
                'value_options' => $this->carRepository->fetchAllCarForSelect(),
            ],
            'attributes' => [
                'class' => 'js-chosen',
                'name'  => "car_id"
            ],
        ]);

        $this->add([
            'type' => Element\Text::class,
            'name' => 'insurer_name',
            'options' => [
                'label' => 'Страховая компания',
                'label_attributes' => [
                    'class' => 'my-1 text-dark font-weight-bold',
                ],
            ],
            'attributes' => [
                'maxlength' => 180,
                'id' => 'insurer_name',
                'class' => 'form-control',
                'placeholder' => 'Наименование страховой компании',
                'list' => 'allcontractor'
            ],
        ]);

        $this->add([
            'type' => Element\Date::class,
            'name' => 'insurance_start_date',
            'options' => [
                'label' => 'Дата начала действия',
                'label_attributes' => [
                    'class' => 'mb-1 text-dark font-weight-bold',
                ],
                'format' => 'Y-m-d',
            ],
            'attributes' => [
                'class' => 'form-control',
                'value' => date('Y-m-d'),
            ],
        ]);

        $this->add([
            'type' => Element\Date::class,
            'name' => 'insurance_end_date',
            'options' => [
                'label' => 'Дата окончания действия',
                'label_attributes' => [
                    'class' => 'mb-1 text-dark font-weight-bold',
                ],
                'format' => 'Y-m-d',
            ],
            'attributes' => [
                'class' => 'form-control',
                'value' => date('Y-m-d'),
            ],
        ]);

        $this->add([
            'type' => Element\Text::class,
            'name' => 'cost',
            'options' => [
                'label' => 'Стоимость аренды',
                'label_attributes' => [
                    'class' => 'mb-1 text-dark font-weight-bold',
                ],
            ],
            'attributes' => [
                'class' => 'form-control',
                'placeholder' => 'Укажите стоимость аренды...',
            ]
        ]);

        $this->add([
            'type' => Element\Csrf::class,
            'name' => 'csrf',
            'options' => [
                'csrf_options' => [
                    'timeout' => 600,
                ],

            ],
        ]);

        $this->add([
            'type' => Element\Submit::class,
            'name' => 'insuranceSubmit',
            'attributes' => array(
                'style' => 'width:100px;',
                'value' => 'Сохранить',
                'id'    => 'request_submit',
                'class' => 'btn btn-primary'
            ),
        ]);

        $this->setInputFilter($this->createInputFilter());
    }

    public function createInputFilter()
    {
        $inputFilter = new InputFilter\InputFilter();

        $inputFilter->add([
            'name' => 'csrf',
            'validators' => [
                ['name' => Validator\Csrf::class,],
            ],
        ]);

        $inputFilter->add([
            'name' => 'car_id',
            'required' => true,
            'filters' => [
                ['name' => Filter\StripTags::class],
                ['name' => Filter\StringTrim::class],
            ],
            'validators' => [
                ['name' => Validator\NotEmpty::class,],
                ['name' => I18n\Validator\IsInt::class,],
            ],
        ]);

        $inputFilter->add([
            'name' => 'insurer_name',
            'required' => true,
            'filters' => [
                ['name' => Filter\StripTags::class],
                ['name' => Filter\StringTrim::class],
            ],
            'validators' => [
                ['name' => Validator\NotEmpty::class,],
                [
                    'name' => Validator\StringLength::class,
                    'options' => [
                        'max' => 250,
                    ],
                ],
            ],
        ]);

        $inputFilter->add([
            'name' => 'insurance_start_date',
            'required' => true,
            'validators' => [
                ['name' => Validator\NotEmpty::class,],
                ['name' => Validator\Date::class,],
            ],
        ]);

        $inputFilter->add([
            'name' => 'insurance_end_date',
            'required' => true,
            'validators' => [
                ['name' => Validator\NotEmpty::class,],
                ['name' => Validator\Date::class,],
            ],
        ]);

        $inputFilter->add([
            'name' => 'cost',
            'required' => true,
            'filters' => [
                ['name' => Filter\StripTags::class],
                ['name' => Filter\StringTrim::class],
                [
                    'name' => Filter\PregReplace::class,
                    'options' => [
                        'pattern'     => '/,/',
                        'replacement' => '.',
                    ]
                ],
            ],
            'validators' => [
                [
                    'name' => I18n\Validator\IsFloat::class,
                    'options' => ['locale' => 'en_US']
                ],
            ],
        ]);

        return $inputFilter;
    }

    public function addImageField(string $folder_path)
    {
        $this->add([
            'type' => Element\File::class,
            'name' => 'image',
            'options' => [
                'label' => 'Добавить изображение',
                'label_attributes' => [
                    'class' => 'my-1 text-dark font-weight-bold',
                ],
            ],
            'attributes' => [
                'class' => 'mb-1 form-control-file',
                'id' => 'image',
                'multiple' => true, //разрешаем загрузку нескольких файлов

            ]
        ]);

        $this->getIterator()->setPriority('insuranceSubmit', -1);
        $this->getIterator()->setPriority('csrf', -1);

        $this->getInputFilter()->add(
            [
                'name' => 'image',
                'allow_empty' => true,
                //для файлов сначала валидаторы
                'validators' => [
                    ['name' => Validator\NotEmpty::class],
                    [
                        'name' => Validator\File\MimeType::class,
                        'options' => [
                            'mimeType' => 'application/pdf', 'image/png, image/jpeg, image/jpg, image/gif'
                        ],
                    ],
                    [
                        'name' => Validator\File\Size::class,
                        'options' => [
                            'min' => '3kB',
                            'max' => '15MB'
                        ],
                    ],
                ],
                'filters' => [
                    ['name' => Filter\StripTags::class],
                    ['name' => Filter\StringTrim::class],
                    [
                        'name' => Filter\File\RenameUpload::class,
                        'options' => [
                            'target' => getcwd() . DIRECTORY_SEPARATOR . $folder_path,
                            'use_upload_name' => false,
                            'use_upload_extension' => true,
                            'overwrite' => false,
                            'randomize' => true
                        ]
                    ]
                ]
            ]
        );
    }
}
