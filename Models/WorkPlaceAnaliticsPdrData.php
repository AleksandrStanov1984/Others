<?

class WorkPlaceAnaliticsPdrData extends CActiveRecord
{

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    protected function beforeSave()
    {
        if (!$this->id) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        return parent::beforeSave();
    }

    public function tableName()
    {
        return '{{workplace_analitics_pdr_data}}';
    }

    public function relations()
    {
        return [
            'bossEmploer' => [self::BELONGS_TO, 'OkLsEmployees', 'idBoss'],
            'pdr' => [self::BELONGS_TO, 'OkSprPdr', 'idPdr'],
        ];
    }

    public static function getPdrInfo($pdrId, $period){

    }

}