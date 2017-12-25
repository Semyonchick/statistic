<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\components\BX24;
use yii\console\Controller;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class SourcesController extends Controller
{
    public $url = 'https://holding-gel.bitrix24.ru/rest/112/g253etq68gqhnlvt/';
    private $_bx = false;

    public function actionUpdateListSources()
    {
        $sourceList = $this->bx('crm.status.list', ['filter' => ['ENTITY_ID' => 'SOURCE']]);

        $params = [
            'IBLOCK_TYPE_ID' => 'lists',
            'IBLOCK_ID' => '30',
            'FIELD_ID' => 'PROPERTY_112',
        ];
        $data = current($this->bx('lists.field.get', $params));

        $list = array_map(function ($row) {
            return $row['NAME'];
        }, array_filter($sourceList, function ($row) use ($data) {
            return !in_array($row['NAME'], $data['DISPLAY_VALUES_FORM']);
        }));


        if (count($list)) {
            print_r($list);
            $this->bx('lists.field.update', [], $params + [
                    'FIELDS' => $data + [
                            'LIST_TEXT_VALUES' => implode(PHP_EOL, $list),
                        ],
                ]);
        }
    }

    public function bx($method, $get = null, $post = null)
    {
        if (!$this->_bx) {
            $this->_bx = new BX24(['url' => $this->url]);
        }
        return $this->_bx->run($method, $get, $post);
    }
}
