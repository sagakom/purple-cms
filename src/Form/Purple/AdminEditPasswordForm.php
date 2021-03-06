<?php
namespace App\Form\Purple;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;

class AdminEditPasswordForm extends Form
{
    protected function _buildValidator(Validator $validator)
    {
        $validator->allowEmpty('password')
                  ->add('password', [
                        'size' => [
                            'rule'    => ['lengthBetween', 6, 20],
                            'message' => 'Password need to be at least 6 characters and maximum 20 characters'
                        ]
                    ])
                  ->allowEmpty('repeatpassword')
                  ->add('repeatpassword', [
                        'size'    => [
                            'rule'    => ['lengthBetween', 6, 20],
                            'message' => 'Password need to be at least 6 characters and maximum 20 characters'
                        ]
                    ])
                  ->requirePresence('username')
                  ->notEmpty('username', 'Please fill this field')
                  ->add('username', [
                        'minLength' => [
                            'rule'    => ['minLength', 6],
                            'message' => 'Username need to be at least 6 characters'
                        ],
                        'maxLength' => [
                            'rule'    => ['maxLength', 15],
                            'message' => 'Username is maximum 15 characters'
                        ],
                        'alphaNumeric' => [
                            'rule'    => 'alphaNumeric',
                            'message' => 'Username is alpha numeric'
                        ]
                    ])
                  ->requirePresence('email')
                  ->notEmpty('email', 'Please fill this field')
                  ->add('email', [
                        'validFormat' => [
                            'rule'    => 'email',
                            'message' => 'Email must be in valid format',
                        ]
                    ])
                  ->requirePresence('id')
                  ->notEmpty('id', 'Please fill this field')
                  ->add('id', [
                        'isInteger' => [
                            'rule'    => ['isInteger'],
                            'message' => 'User id must be an integer value'
                        ]
                    ]);

        return $validator;
    }

    protected function _execute(array $data)
    {
        return true;
    }
}