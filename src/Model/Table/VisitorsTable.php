<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use App\Purple\PurpleProjectSettings;
use Carbon\Carbon;

class VisitorsTable extends Table
{
	public function initialize(array $config)
	{
        $this->setTable('visitors');
		$this->setPrimaryKey('id');
    }
    public function beforeSave($event, $entity, $options)
    {
        $purpleSettings = new PurpleProjectSettings();
        $timezone       = $purpleSettings->timezone();
        $date           = Carbon::now($timezone);
        $dateCreated    = date('Y-m-d', strtotime($date));
        $timeCreated    = date('H:i:s', strtotime($date));

        if ($entity->isNew()) {
            $entity->date_created = $dateCreated;
            $entity->time_created = $timeCreated;
        }
    }
    public function totalAllVisitors() 
    {
        $totalVisitors = $this->find()->count();
        return $totalVisitors;
    }
    public function totalUniqueVisitors() 
    {
        $totalVisitors = $this->find('all',
                array('fields'=> array('DISTINCT date_created'))
            )->count();
        return $totalVisitors;
    }
    public function totalMobileVisitors() 
    {
        $totalVisitors = $this->find()->where(['device' => 'Phone'])->orWhere(['device' => 'Tablet'])->count();
        return $totalVisitors;
    }
    public function lastTwoWeeksTotalVisitors() 
    {
        $arrayDays = array();
        for ($day = 1; $day <= 14; $day++) {
            $data = date('Y-m-d', strtotime("-".$day." days"));

            $totalVisitors = $this->find()->where(['DATE(date_created)' => $data])->count();
            $arrayDays[] = $totalVisitors;
        }
        
        return array_reverse($arrayDays);
    }
    public function lastTwoWeeksBeforeTotalVisitors() 
    {
        $arrayDays = array();
        for ($day = 15; $day <= 28; $day++) {
            $data = date('Y-m-d', strtotime("-".$day." days"));

            $totalVisitors = $this->find()->where(['DATE(date_created)' => $data])->count();
            $arrayDays[] = $totalVisitors;
        }
        
        return array_reverse($arrayDays);
    }
    public function lastSixMonthVisitors() 
    {
    	$arrayMonth = array();
		for ($month = 1; $month <= 6; $month++) {
			$arrayMonth[] = strtoupper(date('M', strtotime("-".$month." month")));
		}
		return array_reverse($arrayMonth);
    }
    public function lastSixMonthTotalVisitors() 
    {
    	$arrayMonth = array();
		for ($month = 1; $month <= 6; $month++) {
			$data = date('Y-m', strtotime("-".$month." month"));
			$explodeData = explode('-', $data);

	    	$totalVisitors = $this->find()->where(['YEAR(date_created)' => $explodeData[0], 'MONTH(date_created)' => $explodeData[1]])->count();
			$arrayMonth[] = $totalVisitors;
		}
		
		return array_reverse($arrayMonth);
    }
    public function lastSixMonthTotalUniqueVisitors() 
    {
    	$arrayMonth = array();
		for ($month = 1; $month <= 6; $month++) {
			$data = date('Y-m', strtotime("-".$month." month"));
			$explodeData = explode('-', $data);

	    	$totalVisitors = $this->find('all',
			    array('fields'=> array('DISTINCT date_created'))
			)->where(['YEAR(date_created)' => $explodeData[0], 'MONTH(date_created)' => $explodeData[1]])->count();
			$arrayMonth[] = $totalVisitors;
		}
		
		return array_reverse($arrayMonth);
    }
    public function lastSixMonthTotalMobileVisitors() 
    {
        $arrayMonth = array();
        for ($month = 1; $month <= 6; $month++) {
            $data = date('Y-m', strtotime("-".$month." month"));
            $explodeData = explode('-', $data);

            $totalVisitors = $this->find()->where(['YEAR(date_created)' => $explodeData[0], 'MONTH(date_created)' => $explodeData[1], 'OR' => [['device' => 'Phone'], ['device' => 'Tablet']]])->count();
            $arrayMonth[] = $totalVisitors;
        }
        
        return array_reverse($arrayMonth);
    }
    public function countVisitorsInMonth($year = NULL, $month = NULL) 
    {
    	if ($year == NULL) {
    		$usedYear = date('Y');
    	}
    	else {
    		$usedYear = $year;
    	}

    	if ($month == NULL) {
    		$usedMonth = date('m');
    	}
    	else {
    		$usedMonth = $month;
    	}

    	$totalVisitors = $this->find()->where(['YEAR(date_created)' => $usedYear, 'MONTH(date_created)' => $usedMonth])->count();
    	return $totalVisitors;
    }
    public function totalVisitorsDate($date) 
    {
        $totalVisitors = $this->find()->where(['DATE(date_created)' => $date])->count();
        return $totalVisitors;
    }
    public function totalUniqueVisitorsDate($date) 
    {
        $totalVisitors = $this->find('all', ['fields'=> array('DISTINCT date_created')]
            )->where(['DATE(date_created)' => $date])->count();
        return $totalVisitors;
    }
    public function totalMobileVisitorsDate($date) 
    {
        $totalVisitors = $this->find()->where(['DATE(date_created)' => $date, 'OR' => [['device' => 'Phone'], ['device' => 'Tablet']]])->count();
        return $totalVisitors;
    }
    public function visitorsPlatform($browser, $month = NULL, $year = NULL)
    {
        if ($year == NULL && $month == NULL) {
            $totalVisitorsBrowser = $this->find('all', [
                'conditions' => ['platform LIKE' => '%'.$browser.'%']
            ])->count();
        }
        else {
            $totalVisitorsBrowser = $this->find('all', [
                'conditions' => ['platform LIKE' => '%'.$browser.'%']
            ])->where(['YEAR(date_created)' => $year, 'MONTH(date_created)' => $month])->count();    
        }
        return $totalVisitorsBrowser;
    }
    public function checkVisitor($ip, $created, $browser, $platform, $device)
    {
        $year  = date('Y', strtotime($created));
        $month = date('m', strtotime($created));
        $date  = date('d', strtotime($created));
        $fullDate = $year.'-'.$month.'-'.$date;

        $query = $this->find()->where(['DATE(date_created)' => $fullDate, 'ip' => $ip, 'browser' => $browser, 'platform' => $platform, 'device' => $device]);
        return $query->count(); 
    }
}