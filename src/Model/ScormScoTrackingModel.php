<?php

namespace Convertiv\Scorm\Model;

use Illuminate\Database\Eloquent\Model;

class ScormScoTrackingModel extends Model
{
    protected $fillable = [
        "user_id",
        "sco_id",
        "uuid",
        "progression",
        "score_raw",
        "score_min",
        "score_max",
        "score_scaled",
        "lesson_status",
        "completion_status",
        "session_time",
        "total_time_int",
        "total_time_string",
        "entry",
        "suspend_data",
        "credit",
        "exit_mode",
        "lesson_location",
        "lesson_mode",
        "is_locked",
        "details",
        "latest_date",
        "created_at",
        "updated_at",
    ];

    protected $casts = [
        "details" => "array",
    ];

    /**
     * Create a static array of sorts that can be overriden at the model level
     */
    public static $valid_sorts = ["title", "created_at"];


    public function getTable()
    {
        return config("scorm.table_names.scorm_sco_tracking_table", parent::getTable());
    }

    public function sco()
    {
        return $this->belongsTo(ScormScoModel::class, "sco_id", "id");
    }

    public function user()
    {
        return $this->belongsTo(config("scorm.user_model"));
    }

    public function scorm()
    {
        return $this->belongsTo(ScormModel::class, "sco_id", "id");
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
            "user_id" => $this->user_id,
            "sco_id" => $this->sco_id,
            "lesson_status" => $this->lesson_status,
            "updated_at" => $this->updated_at,
            "score_raw" => $this->score_raw,
            "score_max" => $this->score_max,
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
