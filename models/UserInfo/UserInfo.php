<?php

namespace app\models\UserInfo;

use Yii;

/**
 * This is the model class for table "user_info".
 *
 * @property integer $id
 * @property string $name
 * @property integer $type
 * @property string $image
 * @property string $phone
 * @property string $birthday
 * @property string $describe
 * @property string $cost
 */
class UserInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_info';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'phone'], 'required'],
            [['type'], 'integer'],
            [['birthday'], 'safe'],
            [['describe'], 'string'],
            [['cost'], 'number'],
            [['name', 'image'], 'string', 'max' => 255],
            [['phone'], 'string', 'max' => 11],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'type' => 'Type',
            'image' => 'Image',
            'phone' => 'Phone',
            'birthday' => 'Birthday',
            'describe' => 'Describe',
            'cost' => 'Cost',
        ];
    }
}
