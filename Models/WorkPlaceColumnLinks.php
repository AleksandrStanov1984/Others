<?

class WorkPlaceColumnLinks extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return '{{workplace_column_links}}';
    }

    public function defaultScope()
    {
        return array(
            'order'=>'order_index ASC'
        );
    }

    public function relations()
    {
        return [
            'user' => [self::BELONGS_TO, 'User', 'user_id'],
            'column' => [self::BELONGS_TO, 'WorkPlaceColumns', 'column_id'],
        ];
    }

    public static function addLinkColumnId($coulumn, $elementId) {
        $test = WorkPlaceColumnLinks::model()->with('column')->find("t.column_id = $coulumn->id AND t.element_id = $elementId");
        if (!$test) {
            $link = new WorkPlaceColumnLinks();
            $link->column_id = $coulumn->id;
            $link->element_id = $elementId;
            $link->user_id = $coulumn->user_id;
            $link->module = $coulumn->module;
            $link->order_index = count($link->column->links);
            $link->save();
            //$link->createOrder();
        }
        return true;
    }

    public function setOrder($index = -1){
        if ($index != -1) { // обновляем мндексы
            $oldIndex = $this->order_index;
            if ($index == $oldIndex) {
                return true;
            }

            $this->order_index = $index;
            $this->save();

            $list = $this->column->links;

            if ($index > $oldIndex) {
                foreach ($list as $link) {
                    if ($link->id == $this->id) continue;
                    if ($link->order_index < $oldIndex) continue;
                    if ($link->order_index > $index) continue;
                    $link->order_index = $link->order_index - 1;
                    $link->save();
                }
            }
            if ($index < $oldIndex) {
                foreach ($list as $link) {
                    if ($link->id == $this->id) continue;
                    if ($link->order_index < $index) continue;
                    if ($link->order_index > $oldIndex) continue;
                    $link->order_index = $link->order_index + 1;
                    $link->save();
                }
            }
        }
    }

    public static function deleteOldLinks($moduleName, $user = false, $noDeleteIds = []) {
        if (!$user) {
            $user = User::getUserUser();
        }
        // удаляем все строки, которым нет соответствия в таблице колонок
        $criteria = new CDbCriteria();
        $criteria->addCondition("column_id not in (select id from workplace_columns)");
        WorkPlaceColumnLinks::model()->deleteAll($criteria);

        $criteria = new CDbCriteria();
        $criteria->addCondition("module = '$moduleName'");
        $criteria->addCondition("user_id = '$user->id'");
        $criteria->addNotInCondition('element_id', $noDeleteIds);
        WorkPlaceColumnLinks::model()->deleteAll($criteria);
        return true;
    }

    public static function addDefaultLinkModuleName($moduleName, $elementId) {
        $user = User::getUserUser();
        $column = WorkPlaceColumns::model()->find("module = '$moduleName' AND user_id = $user->id AND defaultColumn = 1");
        if ($column) {
            return self::addLinkColumnId($column, $elementId);
        } else {
            return false;
        }
    }

    public static function checkLink($moduleName, $user, $elementId) {
        if (!$user){
            $user = User::getUserUser();
        }
        if (WorkPlaceColumnLinks::model()->find("module = '$moduleName' AND user_id = '$user->id' AND element_id = '$elementId' ")) {
            return true;
        } else {
            return false;
        }
    }

}