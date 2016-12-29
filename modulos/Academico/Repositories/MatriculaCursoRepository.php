<?php

namespace Modulos\Academico\Repositories;

use Modulos\Academico\Models\Matricula;
use Modulos\Core\Repository\BaseRepository;
use DB;
use stdClass;

class MatriculaCursoRepository extends BaseRepository
{
    protected $ofertaCursoRepository;
    protected $matrizCurricularRepository;
    protected $matriculaOfertaDisciplinaRepository;

    public function __construct(
        Matricula $matricula,
        OfertaCursoRepository $oferta,
        MatrizCurricularRepository $matriz,
        MatriculaOfertaDisciplinaRepository $matriculaOferta
    )
    {
        $this->model = $matricula;
        $this->ofertaCursoRepository = $oferta;
        $this->matrizCurricularRepository = $matriz;
        $this->matriculaOfertaDisciplinaRepository = $matriculaOferta;
    }

    public function verifyIfExistsMatriculaByOfertaCursoOrTurma($alunoId, $ofertaCursoId, $turmaId)
    {
        $result = $this->model
            ->join('acd_turmas', function ($join) {
                $join->on('mat_trm_id', '=', 'trm_id');
            })
            ->join('acd_ofertas_cursos', function ($join) {
                $join->on('trm_ofc_id', '=', 'ofc_id');
            })
            ->where('mat_alu_id', '=', $alunoId)
            ->where(function ($query) use ($turmaId, $ofertaCursoId) {
                $query->where('mat_trm_id', '=', $turmaId)
                    ->orWhere('trm_ofc_id', '=', $ofertaCursoId);
            })->get();

        if ($result->count()) {
            return true;
        }

        return false;
    }

    public function verifyIfExistsMatriculaByCursoAndSituacao($alunoId, $cursoId)
    {
        $result = $this->model
            ->join('acd_turmas', function ($join) {
                $join->on('mat_trm_id', '=', 'trm_id');
            })
            ->join('acd_ofertas_cursos', function ($join) {
                $join->on('trm_ofc_id', '=', 'ofc_id');
            })
            ->where('mat_alu_id', '=', $alunoId)
            ->whereNotIn('mat_situacao', ['concluido', 'evadido', 'desistente'])
            ->where('ofc_crs_id', '=', $cursoId)
            ->get();

        if ($result->count()) {
            return true;
        }

        return false;
    }

    public function verifyIfExistsMatriculaInCursoGraducao($alunoId)
    {
        $result = $this->model
            ->join('acd_turmas', function ($join) {
                $join->on('mat_trm_id', '=', 'trm_id');
            })
            ->join('acd_ofertas_cursos', function ($join) {
                $join->on('trm_ofc_id', '=', 'ofc_id');
            })
            ->join('acd_cursos', function ($join) {
                $join->on('ofc_crs_id', '=', 'crs_id')->where('crs_nvc_id', '=', '3');
            })
            ->where('mat_alu_id', '=', $alunoId)
            ->whereNotIn('mat_situacao', ['concluido', 'evadido', 'desistente'])
            ->get();

        if ($result->count()) {
            return true;
        }

        return false;
    }

    public function findAll(array $options, array $select = null, array $order = null)
    {
        $query = $this->model
            ->join('acd_turmas', function ($join) {
                $join->on('mat_trm_id', '=', 'trm_id');
            })
            ->join('acd_ofertas_cursos', function ($join) {
                $join->on('trm_ofc_id', '=', 'ofc_id');
            })
            ->join('acd_cursos', function ($join) {
                $join->on('ofc_crs_id', '=', 'crs_id');
            })
            ->leftJoin('acd_polos', function ($join) {
                $join->on('mat_pol_id', '=', 'pol_id');
            })
            ->leftJoin('acd_grupos', function ($join) {
                $join->on('mat_grp_id', '=', 'grp_id');
            })
            ->join('acd_alunos', function ($join) {
                $join->on('mat_alu_id', '=', 'alu_id');
            })->join('gra_pessoas', function ($join) {
                $join->on('alu_pes_id', '=', 'pes_id');
            });

        if (!empty($options)) {
            foreach ($options as $key => $value) {
                $query = $query->where($key, '=', $value);
            }
        }

        if (!is_null($select)) {
            $query = $query->select($select);
        }

        if(!is_null($order)) {
            foreach ($order as $key => $value) {
                $query = $query->orderBy($key, $value);
            }
        }

        return $query->get();
    }

    public function findMatriculaIdByTurmaAluno($alunoId, $turmaId)
    {
        $matricula = DB::table('acd_matriculas')
            ->where('mat_trm_id', '=', $turmaId)
            ->where('mat_alu_id', '=', $alunoId)
            ->first();

        return $matricula;
    }

    public function findDadosByTurmaId($turmaId)
    {
        $dados = DB::table('acd_matriculas')
            ->join('acd_alunos', function ($join) {
                $join->on('mat_alu_id', '=', 'alu_id');
            })
            ->join('gra_pessoas', function ($join) {
                $join->on('alu_pes_id', '=', 'pes_id');
            })->orderBy('pes_nome', 'asc')->get();

        return $dados;
    }

    public function verifyIfAlunoAprovadoLancadoTcc($matriculaId)
    {
        $result = $this->model
            ->join('acd_matriculas_ofertas_disciplinas', 'mof_mat_id', 'mat_id')
            ->join('acd_ofertas_disciplinas', 'mof_ofd_id', 'ofd_id')
            ->join('acd_modulos_disciplinas', 'ofd_mdc_id', 'mdc_id')
            ->join('acd_lancamentos_tccs', 'mat_ltc_id', 'ltc_id')
            ->where('mdc_tipo_disciplina', '=', 'tcc')
            ->where('mof_mat_id', '=', $matriculaId)
            ->whereIn('mof_situacao_matricula', ['aprovado_media', 'aprovado_final'])
            ->whereNotNull('mat_ltc_id')
            ->first();

        if(!is_null($result)) {
            return true;
        }

        return false;
    }

    public function verifyIfAlunoIsAptoOrNot($matriculaId, $ofertaCursoId)
    {
        // busca as informacoes da oferta de curso
        $ofertaCurso = $this->ofertaCursoRepository->find($ofertaCursoId);

        // busca as informacoes da matriz curricular do curso
        $matrizCurricular = $this->matrizCurricularRepository->find($ofertaCurso->ofc_mtc_id);

        // busca todas as disciplinas da matriz do curso
        $disciplinasMatriz = $this->matrizCurricularRepository->getDisciplinasByMatrizId($matrizCurricular->mtc_id)
            ->pluck('mdc_id')->toArray();

        // busca as informações da matricula
        $matricula = $this->find($matriculaId);

        if($matricula->mat_situacao == 'concluido') {
            return 2;
        }

        if($matricula->mat_situacao == 'cursando') {
            $disciplinasAluno = $this->matriculaOfertaDisciplinaRepository->getAllMatriculasByAluno($matricula->mat_alu_id);

            $quantDisciplinasObrigatorias = 0;
            $quantDisciplinasObrigatoriasAprovadas = 0;
            $quantDisciplinasAprovadas = 0;
            $quantDisciplinasMatriz = count($disciplinasMatriz);

            foreach($disciplinasAluno as $disciplina) {
                if($disciplina->mdc_tipo_disciplina == 'obrigatoria') {
                    $quantDisciplinasObrigatorias++;
                }
                // Verifica se a oferta de disciplina está na matriz do curso
                if(in_array($disciplina->mdc_id, $disciplinasMatriz)) {
                    // Caso o aluno foi aprovado na disciplina, incrementa a variavel
                    if(in_array($disciplina->mof_situacao_matricula, ['aprovado_media', 'aprovado_final'])) {
                        $quantDisciplinasAprovadas++;
                        if($disciplina->mdc_tipo_disciplina == 'obrigatoria') {
                            $quantDisciplinasObrigatoriasAprovadas++;
                        }
                    }
                }
            }

            $temTcc = false;
            // Verifica se a matriz possui disciplina tcc
            if($this->matrizCurricularRepository->verifyIfExistsDisciplinaTccInMatriz($matrizCurricular->mtc_id)) {
                // verifica se o aluno foi aprovado e possui Tcc lançado
                if($this->verifyIfAlunoAprovadoLancadoTcc($matricula->mat_id)) {
                    $temTcc = true;
                }
            }

            // Casos de situações
            if(($quantDisciplinasObrigatoriasAprovadas == $quantDisciplinasObrigatorias) && $temTcc) {
                return 1;
            }
        }

        return 0;
    }

    public function getAlunosAptosOrNot($ofertaCursoId, $turmaId, $poloId)
    {
        // busca todas as matriculas da turma
        $matriculas = $this->findAll(['mat_trm_id' => $turmaId, 'mat_pol_id' => $poloId], null, ['pes_nome' => 'asc']);

        $result = [];
        if($matriculas->count()) {
            foreach($matriculas as $matricula) {
                $obj = new StdClass;

                $obj->mat_id = $matricula->mat_id;
                $obj->alu_id = $matricula->alu_id;
                $obj->pes_nome = $matricula->pes_nome;
                $obj->status = 0;
                $obj->data_conclusao = ' --- ';

                if($matricula->mat_situacao == 'concluido') {
                    $obj->status = 2;
                    $obj->data_conclusao = $matricula->mat_data_conclusao;
                    $result[] = $obj;
                    continue;
                }

                if($matricula->mat_situacao == 'cursando') {
                    $obj->status = $this->verifyIfAlunoIsAptoOrNot($matricula->mat_id, $ofertaCursoId);
                }

                $result[] = $obj;
            }
        }

        return $result;
    }

    public function concluirMatricula($matriculaId, $ofertaCursoId)
    {
        // verifica se matricula existe
        $matricula = $this->find($matriculaId);

        if($matricula) {
            // verifica se matricula está apta para conclusao
            $result = $this->verifyIfAlunoIsAptoOrNot($matriculaId, $ofertaCursoId);

            if($result == 1) {
                $matricula->mat_situacao = 'concluido';
                $matricula->mat_data_conclusao = date('Y-m-d');
                $matricula->save();

                return true;
            }
        }

        return false;
    }
}
