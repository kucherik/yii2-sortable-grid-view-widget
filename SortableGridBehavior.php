<?php
/**
 * @link https://github.com/himiklab/yii2-sortable-grid-view-widget
 * @copyright Copyright (c) 2014-2017 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace kucherik\sortablegrid;

use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;

/**
 * Behavior for sortable Yii2 GridView widget.
 *
 * For example:
 *
 * ```php
 * public function behaviors()
 * {
 *    return [
 *       'sort' => [
 *           'class' => SortableGridBehavior::className(),
 *           'sortableAttribute' => 'sortOrder',
 *           'scope' => function ($query) {
 *              $query->andWhere(['group_id' => $this->group_id]);
 *           },
 *       ],
 *   ];
 * }
 * ```
 *
 * @author HimikLab
 * @package kucherik\sortablegrid
 */
class SortableGridBehavior extends Behavior
{
    /** @var string database field name for row sorting */
    public $sortableAttribute = 'sortOrder';

    /** @var callable */
    public $scope;

    /** @var callable */
    public $afterGridSort;

    public function events()
    {
        return [ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert'];
    }

    public function gridSort($items)
    {
        /** @var ActiveRecord $model */
        $model = $this->owner;
        if (!$model->hasAttribute($this->sortableAttribute)) {
            throw new InvalidConfigException("Model does not have sortable attribute `{$this->sortableAttribute}`.");
        }

        $newOrder = [];
        $models = [];
        foreach ($items as $old => $new) {
            $models[$new] = $model::findOne($new);
            $newOrder[$old] = $models[$new]->{$this->sortableAttribute} ? $models[$new]->{$this->sortableAttribute} : $new;
        }
        $model::getDb()->transaction(function () use ($models, $newOrder) {
            foreach ($newOrder as $modelId => $orderValue) {
                /** @var ActiveRecord[] $models */
                $models[$modelId]->updateAttributes([$this->sortableAttribute => $orderValue]);
            }
        });

        if (is_callable($this->afterGridSort)) {
            call_user_func($this->afterGridSort, $model);
        }
    }

    public function beforeInsert()
    {
        /** @var ActiveRecord $model */
        $model = $this->owner;
        if (!$model->hasAttribute($this->sortableAttribute)) {
            throw new InvalidConfigException("Invalid sortable attribute `{$this->sortableAttribute}`.");
        }

        $query = $model::find();
        if (is_callable($this->scope)) {
            call_user_func($this->scope, $query);
        }

        /* Override model alias if defined in the model's class */
        $query->from([$model::tableName() => $model::tableName()]);

        $maxOrder = $query->max('{{' . trim($model::tableName(), '{}') . '}}.[[' . $this->sortableAttribute . ']]');
        $model->{$this->sortableAttribute} = $maxOrder + 1;
    }
}
