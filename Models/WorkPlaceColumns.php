<?

class WorkPlaceColumns extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName(): string
    {
        return '{{workplace_columns}}';
    }

    public function relations(): array
    {
        return [
            'user' => [self::BELONGS_TO, 'User', 'user_id'],
            'links' => [self::HAS_MANY, 'WorkPlaceColumnLinks', 'column_id'],
        ];
    }

    const DEFAULT_COUNT = ['conflict' => 2, 'chat' => 2, 'task' => 3];

    public function makeColumnInfo(): stdClass
    {
        $elements = [];
        $links = $this->links;
        if ($links) foreach ($links as $link) {
            $elements[] = $this->module . $link->element_id;
            //$elements[] = array_merge($elements, $this->module . $link->element_id);
        }
        $result = new stdClass();
        $variableName = $this->module."Ids";
        $result->$variableName = $elements;
        $result->id = $this->name;
        $result->title = $this->title;
        $result->base_id = $this->id;
        return $result;
    }

    public static function makeColumnsList($moduleName, $user = false): array
    {
        if (!$user) {
            $user = User::getUserUser();
        }

        WorkPlaceColumns::checkDefaultColumns($moduleName, $user);
        $criteria = new CDbCriteria();
        $criteria->addCondition("user_id = '$user->id'");
        $criteria->addCondition("module = '$moduleName'");
        $criteria->order = "sort ASC";
        $columns = WorkPlaceColumns::model()->findAll($criteria);
        $columnOn = new stdClass();
        $columnOrder = [];
        foreach ($columns as $column) {
            $name = $column->name;
            $columnOn->$name = $column->makeColumnInfo();
            $columnOrder[] = $name;
        }
        return ['columnsOn' => $columnOn, 'columnOrder' => $columnOrder, 'columns'=>$columns];
    }

    public static function makeNewColumn($moduleName, $columnName = '', $user = false): bool
    {
        if (!$user) {
            $user = User::getUserUser();
        }
        $count = WorkPlaceColumns::model()->count("module = '$moduleName' And user_id = '$user->id'");
        $default = 0;
        if ($count == 0) {
            $default = 1;
        }
        $columnId = $count+1;
        if ($columnName == '') {
            $columnName = "Новые разговоры";
          //  $columnName = "default column $columnId";
        }

        $column = new WorkPlaceColumns();
        $column->module = $moduleName;
        $column->user_id = $user->id;
        if($columnId == 1)
            $column->title = 'Новые разговоры';
        if($columnId == 2){
            $column->title = 'Рабочие моменты';
        }else{
            $column->title = $columnName;
        }
       // $column->title = $columnName;
        $column->name = 'column' . $columnId;
        $column->sort = $columnId;
        $column->defaultColumn = $default;
        $column->save();
        return true;
    }

    public static function makeNewColumnConflict($moduleName, $columnName = '', $user = false): bool
    {
        if (!$user) {
            $user = User::getUserUser();
        }
        $count = WorkPlaceColumns::model()->count("module = '$moduleName' And user_id = '$user->id'");
        $default = 0;
        if ($count == 0) {
            $default = 1;
        }
        $columnId = $count+1;
        if ($columnName == '') {
            $columnName = "Новые конфликты";
            //  $columnName = "default column $columnId";
        }

        $column = new WorkPlaceColumns();
        $column->module = $moduleName;
        $column->user_id = $user->id;
        if($columnId == 1)
            $column->title = 'Новые конфликты';
        if($columnId == 2){
            $column->title = 'Разрешаемые конфликты';
        }else{
            $column->title = $columnName;
        }
        // $column->title = $columnName;
        $column->name = 'column' . $columnId;
        $column->sort = $columnId;
        $column->defaultColumn = $default;
        $column->save();
        return true;
    }

    public static function makeDefaultColumns($moduleName, $user = false){
        if (!$user) {
            $user = User::getUserUser();
        }
        $count = WorkPlaceColumns::model()->count("module = '$moduleName' And user_id = '$user->id'");
        while ($count < self::DEFAULT_COUNT[$moduleName]) {
            self::makeNewColumnConflict($moduleName, '', $user);
            $count = WorkPlaceColumns::model()->count("module = '$moduleName' And user_id = '$user->id'");
        }
    }

    public static function makeDefaultCol($moduleName, $user = false){
        if (!$user) {
            $user = User::getUserUser();
        }
        $count = WorkPlaceColumns::model()->count("module = '$moduleName' And user_id = '$user->id'");
        while ($count < self::DEFAULT_COUNT[$moduleName]) {
            self::makeNewColumn($moduleName, '', $user);
            $count = WorkPlaceColumns::model()->count("module = '$moduleName' And user_id = '$user->id'");
        }
    }

    public static function checkDefaultColumns($moduleName, $user = false){
        if (!$user) {
            $user = User::getUserUser();
        }
        $count = WorkPlaceColumns::model()->count("module = '$moduleName' And user_id = '$user->id'");
        while ($count < self::DEFAULT_COUNT[$moduleName]) {
            self::makeDefaultColumns($moduleName, $user);
        }
    }

    public static function getFirstDefaultColumns($moduleName, $user = false){
        if (!$user) {
            $user = User::getUserUser();
        }
        return WorkPlaceColumns::model()->find("module = '$moduleName' And user_id = '$user->id' And name = 'column2'");
    }

    public static function updateColumns($module, $columns, $user = false): array
    {
        $result = ['success'=>false, 'error'=>'Не предвиденная ошибка'];
        try {
            if (!$user) {
                $user = User::getUserUser();
            }
            $i = 0;
            foreach ($columns as $column) {
                $i++;
                $columnId = $column['id'];
                $baseColumn = WorkPlaceColumns::model()->find("module = '$module' AND user_id='$user->id' AND name = '$columnId'");
                $save = false;
                if ($baseColumn) {
                    if ($baseColumn->sort != $i) {
                        $baseColumn->sort = $i;
                        $save = true;
                    }
                    if ($baseColumn->title != $column['title']) {
                        $baseColumn->title = $column['title'];
                        $save = true;
                    }
                    if ($save) {
                        $baseColumn->save();
                    }
                }
            }
            $result['success'] = true;
        } catch (Exception $e) {
            $result['error'] = $e->getMessage() . "[".$e->getFile()."][".$e->getLine()."]";
        }
        return $result;
    }

    public static function moveElementToColumn($moduleName, $oldColumnName, $newColumnName, $elementId, $index, $user=false): bool
    {
        $result = false;
        if (!$user) {
            $user = User::getUserUser();
        }

            $old = WorkPlaceColumns::model()->find("module = '$moduleName' AND user_id = '$user->id' AND name = '$oldColumnName'");
            $new = WorkPlaceColumns::model()->with('links')->find("t.module = '$moduleName' AND t.user_id = '$user->id' AND t.name = '$newColumnName'");
            $link = WorkPlaceColumnLinks::model()->find("column_id = '$old->id' and element_id = '$elementId'");

            if ($old && $new) {
                if ($oldColumnName != $newColumnName) {

                    if ($link) {
                        $count = count($new->links);
                        $link->column_id = $new->id;
                        $link->order_index = $count + 1;
                        $link->save();
                    }
                    $old->updateOrder();
                }
                if ($link->order_index != $index && $index != -1) {
                    $link->setOrder($index);
                }
                $result = true;
            }


        return $result;
    }

    public function updateOrder(){
        $k = 0;
        foreach ($this->links as $link) {
            if ($link->order_index != $k) {
                $link->order_index = $k;
                $link->save();
            }
            $k++;
        }
    }

    public static function addNewColumn($moduleName, $columnName = '', $user = false): bool
    {
        if (!$user) {
            $user = User::getUserUser();
        }
        self::checkDefaultColumns($moduleName, $user);
        self::makeNewColumn($moduleName, $columnName, $user);
        return true;
    }

    public static function updateColumn($moduleName, $columnId, $text, $user = false){
        if (!$user) {
            $user = User::getUserUser();
        }
        $column = WorkPlaceColumns::model()->find("module = '$moduleName' AND user_id = '$user->id' AND name = '$columnId' ");

        if ($column) {
            $column->title = $text;
            $column->save();
            return true;
        } else {
            return "Ошибка обновления поля";
        }
    }

    /**
     * Удаление каталога
     * @param $moduleName
     * @param $columnId
     * @param false $user
     * @return bool|string
     */
    public static function deleteColumn($moduleName, $columnId, $user = false){
        if (!$user) {
            $user = User::getUserUser();
        }
        $column = WorkPlaceColumns::model()->find("module = '$moduleName' AND user_id = '$user->id' AND name = '$columnId' ");

        if ($column) {
            if (count($column->links) > 0) {
                $defaultColumn = self::getFirstDefaultColumns($moduleName, $user);
                if ($defaultColumn) {
                    foreach ($column->links as $link) {
                        self::moveElementToColumn($moduleName, $column->name, $defaultColumn->name, $link->element_id, -1, $user);
                    }
                }
                $column = WorkPlaceColumns::model()->find("module = '$moduleName' AND user_id = '$user->id' AND name = '$columnId' ");
            }


            if (count($column->links) == 0) {
                $column->delete();
                return true;
            } else {
                return "Колонка не пустая!!!";
            }
        } else {
            return false;
        }
    }

}