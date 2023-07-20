<?php

namespace App\Base;

use Gobiz\Support\Traits\CachedPropertiesTrait;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use Jenssegers\Mongodb\Eloquent\Model as BaseModel;

/**
 * Class MongoModel
 *
 * @method static static find($id)
 * @mixin BaseModel
 */
class MongoModel extends BaseModel
{
    use CachedPropertiesTrait;

    protected $connection = 'mongodb';
    protected $guarded = ['_id'];

    /**
     * @return ObjectId
     */
    public function getMongoIdAttribute()
    {
        return $this->getCachedProperty('mongoId', function () {
            return new ObjectId($this->getKey());
        });
    }

    /**
     * @return Collection
     */
    public function getMongoCollection()
    {
        return $this->getConnection()->getMongoDB()->selectCollection($this->getTable());
    }

    /**
     * @return Collection
     */
    public static function queryMongo()
    {
        return (new static())->getMongoCollection();
    }
}
