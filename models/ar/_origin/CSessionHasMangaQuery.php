<?php

namespace app\models\ar\_origin;

use app\models\ar;

/**
 * This is the ActiveQuery class for [[CSessionHasManga]].
 *
 * @see CSessionHasManga
 */
class CSessionHasMangaQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return CSessionHasManga[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return CSessionHasManga|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
