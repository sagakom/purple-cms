<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Menu extends Entity
{
	protected $_accessible = [
		'*' => true,
		'id' => false,
	];
	protected function _getTextStatus()
    {
        if ($this->status == '0') {
            return 'Draft';
        }
        elseif ($this->status == '1') {
            return 'Publish';
        }
    }
}