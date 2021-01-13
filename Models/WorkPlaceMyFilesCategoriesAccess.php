<?

class WorkPlaceMyFilesCategoriesAccess extends CActiveRecord
{

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName(): string
    {
        return '{{workplace_my_files_categories_access}}';
    }


    public function relations(): array
    {
        return [
            'user' => [self::BELONGS_TO, 'User', 'user_id'],
            'catalog' => [self::BELONGS_TO, 'WorkPlaceMyFilesCategories', 'category_id'],
        ];
    }

    protected function beforeSave(): bool
    {
        if (!$this->id) {
            $this->created_at = date('Y-m-d H:i:s');
            $this->updated_at = date('Y-m-d H:i:s');
            $this->created_user = Yii::app()->user->id;
        } else {
            $this->updated_at = date('Y-m-d H:i:s');
        }
        return parent::beforeSave();
    }

    public static function addUserToCatalog($catalogId, $users, $access_id, $useTransaction = true): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        if ($useTransaction) $transaction = Yii::app()->db->beginTransaction();
        try {
            if (is_array($users)) {
                foreach ($users as $uId) {
                    $access = WorkPlaceMyFilesCategoriesAccess::model()->find("category_id = '$catalogId' AND user_id = '$uId'");
                    if ($access) {
                        if ($access->access != $access_id) {
                            $access->access = $access_id;
                            $access->save();
                        }
                    } else {
                        $access = new WorkPlaceMyFilesCategoriesAccess();
                        $access->category_id = $catalogId;
                        $access->user_id = $uId;
                        $access->access = $access_id;
                        $access->save();
                    }
                }
            } else {
                $access = WorkPlaceMyFilesCategoriesAccess::model()->find("category_id = '$catalogId' AND user_id = '$users'");
                if ($access) {
                    if ($access->access != $access_id) {
                        $access->access = $access_id;
                        $access->save();
                    }
                } else {
                    $access = new WorkPlaceMyFilesCategoriesAccess();
                    $access->category_id = $catalogId;
                    $access->user_id = $users;
                    $access->access = $access_id;
                    $access->save();
                }
            }
            if ($useTransaction) $transaction->commit();
            $result['success'] = true;
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            if ($useTransaction) $transaction->rollback();
            $result['status'] = 500;
        }
        return $result;
    }

    public static function deleteUserFromCatalog($catalogId, $userId): bool
    {
        $access = WorkPlaceMyFilesCategoriesAccess::model()->find("category_id = '$catalogId' AND user_id = '$userId'");
        if ($access) {
            $access->delete();
        }
        return true;
    }

    public static function deleteCatalog($catalogId): bool
    {
        $access = WorkPlaceMyFilesCategoriesAccess::model()->find("category_id = '$catalogId'");
        if ($access) {
            $access->delete();
            return true;
        } else {
            return false;
        }
    }

    public static function renameCatalog($catalogId, $name): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 400];
        $user = Yii::app()->user->id;
        $access = WorkPlaceMyFilesCategories::model()->findByPk($catalogId);
        if ($access) {
            //if($access->created_user == $user) {//
            // if($access->user_id == $user) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $access->title = $name;
                $access->save();

                $result['out']['id'] = $catalogId;
                $result['out']['name'] = $access->title;
                $result['status'] = 200;
                $result['success'] = true;

                $transaction->commit();
            } catch (Exception $e) {
                $result['error'] = 'Ошибка переименования каталога ' . $e->getMessage();
                $transaction->rollback();
                $result['status'] = 500;
            }
//            }else{
//                $result['error'] = 'У Вас нет прав';
//                $result['success'] = false;
//                $result['status'] = 500;
//            }
        }
        return $result;
    }

    public static function valid($nameFile): string
    {
        $nameFile = explode("\/.", $nameFile);
        return $nameFile[0];
    }


    public static function changeAccessToCatalog(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 400];

        $access_id = Yii::app()->request->getParam('access_id', 0);
        $catalog_id = Yii::app()->request->getParam('catalog_id', 0);
        $user_id = Yii::app()->request->getParam('user_id', 0);

        $catalog = WorkPlaceMyFilesCategories::model()->findByPk($catalog_id);

        $transaction = Yii::app()->db->beginTransaction();
        if ($catalog) {
                $category = WorkPlaceMyFilesCategoriesAccess::model()->find("category_id = '$catalog_id' AND user_id = '$user_id'");
               // if ($category->created_user == Yii::app()->user->id) {

                //    $cr_user = $category->created_user;
                    if ($category) {
                        $cr_user = $category->created_user;
                        try {
                            if ($access_id != 3) {

                                $catalog->view_user = json_encode($user_id);
                                $catalog->save();

                                $category->access = $access_id;
                                $category->save();

                                $result['status'] = 200;
                                $result['success'] = true;
                                $transaction->commit();
                            } else {
                                $catalog->view_user = json_encode($category->created_user);
                                $catalog->save();
                                $category3 = WorkPlaceMyFilesCategoriesAccess::model()->find("category_id = '$catalog_id' and access < 4");

                                if ($category3) {
                                    $category->user_id = $cr_user;
                                    $category->created_user = $user_id;
                                    $category->access = 0;
                                    $category->save();
                                }

                                $category2 = WorkPlaceMyFilesCategoriesAccess::model()->findAll("category_id = '$catalog_id' and access < 4");
                                foreach ($category2 as $c) {
                                    if ($c->created_user != $user_id) {
                                        $c->created_user = $user_id;
                                        $c->save();
                                    }
                                }
                                $result['status'] = 200;
                                $result['success'] = true;
                                $transaction->commit();
                            }
                        } catch (Exception $e) {
                            $result['error'] = 'Не удалось дать доступ к дириктории ' . $e->getMessage();
                            $transaction->rollback();
                            $result['status'] = 500;
                        }
                    } else {
                        $result['error'] = 'Каталог не может быть без владельца. Операция откланена!';
                        $transaction->rollback();
                        $result['success'] = false;
                        $result['status'] = 500;
                    }
//                } else {
//                    $result['error'] = 'У Вас нет прав!';
//                    $transaction->rollback();
//                    $result['success'] = false;
//                    $result['status'] = 500;
//                }
        }
        return $result;
    }
}

