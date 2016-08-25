<?php

namespace Modulos\Academico\Models;

use Illuminate\Support\Facades\DB;
use Modulos\Core\Model\BaseModel;

class OfertaCurso extends BaseModel
{
    protected $table = 'acd_ofertas_cursos';

    protected $primaryKey = 'ofc_id';

    protected $fillable = [
        'ofc_crs_id',
        'ofc_mtc_id',
        'ofc_mdl_id',
        'ofc_ano'
    ];

    protected $searchable = [
        'ofc_ano' => 'like'
    ];

    public function curso()
    {
        return $this->belongsTo('Modulos\Academico\Models\Curso', 'ofc_crs_id', 'crs_id');
    }

    public function matriz()
    {
        return $this->belongsTo('Modulos\Academico\Models\MatrizCurricular', 'ofc_mtc_id', 'mtc_id');
    }

    public function modalidade()
    {
        return $this->belongsTo('Modulos\Academico\Models\Modalidade', 'ofc_mdl_id', 'mdl_id');
    }

    public function polos()
    {
        return $this->belongsToMany('Modulos\Seguranca\Models\Polo', 'acd_polos_ofertas_cursos', 'poc_pol_id', 'poc_ofc_id');
    }

    public function turmas()
    {
        return $this->hasMany('Modulos\Academico\Models\Turma', 'trm_ofc_id', 'ofc_id');
    }

    public function getOfcCrsIdAttribute($value)
    {
        return DB::table('acd_cursos')->where('crs_id', $value)->value('crs_nome');
    }

    public function getOfcMdlIdAttribute($value)
    {
        return DB::table('acd_modalidades')->where('mdl_id', $value)->value('mdl_nome');
    }
}
