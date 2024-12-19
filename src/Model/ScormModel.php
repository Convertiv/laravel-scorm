<?php

namespace Convertiv\Scorm\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $uuid
 * @property string $title
 * @property string $version
 * @property string $entryUrl
 */
class ScormModel extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "id",
        "resource_id",
        "resource_type",
        "title",
        "origin_file",
        "version",
        "ratio",
        "uuid",
        "identifier",
        "entry_url",
        "created_at",
        "updated_at",
    ];

    /**
     * Create a static array of sorts that can be overriden at the model level
     */
    public static $valid_sorts = ["title", "created_at"];

    /**
     * Get the parent resource model (user or post).
     */
    public function resourceable()
    {
        return $this->morphTo(__FUNCTION__, "resource_type", "resource_id");
    }

    public function getTable()
    {
        return config("scorm.table_names.scorm_table", parent::getTable());
    }

    public function scos()
    {
        return $this->hasMany(ScormScoModel::class, "scorm_id", "id");
    }

    public function started()
    {
        return $this->hasMany(ScormScoTrackingModel::class, "sco_id", "id");
    }

    public function passed()
    {
        return $this->hasMany(ScormScoTrackingModel::class, "sco_id", "id")->where("lesson_status", "passed");
    }

    public function tracking()
    {
        return $this->hasMany(ScormScoTrackingModel::class, "sco_id", "id");
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toRelationshipArray($parent = false, $shallow = false)
    {
        $data = [
            "id" => $this->id,
            "title" => $this->title,
            "identifier" => $this->identifier,
            "url" => config("scorm.url") . "/" . $this->uuid . "/index.html",
        ];

        return $data;
    }

    /**
     * Validate the sort direction
     *
     * @param string $value
     * @return string
     */
    public static function validateDirection(string $value)
    {
        if (strtolower($value) == "asc" || strtolower($value) == "desc") {
            return strtolower($value);
        }
        return "asc";
    }

    /**
     * Given a requested sort param, validate it and format it
     *
     * @param string $value
     * @return string
     */
    public static function validateSort(string $value)
    {
        if (in_array(strtolower($value), static::$valid_sorts)) {
            return strtolower($value);
        }
        return "updated_at";
    }
}
