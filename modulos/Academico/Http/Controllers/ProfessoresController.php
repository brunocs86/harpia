<?php

namespace Modulos\Academico\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modulos\Academico\Http\Requests\ProfessorRequest;
use Modulos\Academico\Repositories\ProfessorRepository;
use Modulos\Core\Http\Controller\BaseController;
use Modulos\Geral\Http\Requests\PessoaRequest;
use Modulos\Geral\Repositories\DocumentoRepository;
use Modulos\Geral\Repositories\PessoaRepository;
use Modulos\Seguranca\Providers\ActionButton\Facades\ActionButton;
use Modulos\Seguranca\Providers\ActionButton\TButton;

class ProfessoresController extends BaseController
{
    protected $professorRepository;
    protected $pessoaRepository;
    protected $documentoRepository;

    public function __construct(ProfessorRepository $professor, PessoaRepository $pessoa, DocumentoRepository $documento)
    {
        $this->professorRepository = $professor;
        $this->pessoaRepository = $pessoa;
        $this->documentoRepository = $documento;
    }

    public function getIndex(Request $request)
    {
        $btnNovo = new TButton();
        $btnNovo->setName('Novo')->setRoute('academico.professores.create')->setIcon('fa fa-plus')->setStyle('btn bg-olive');

        $actionButtons[] = $btnNovo;

        $paginacao = null;
        $tabela = null;

        $tableData = $this->professorRepository->paginateRequest($request->all());

        if ($tableData->count()) {
            $tabela = $tableData->columns(array(
                'prf_id' => '#',
                'pes_nome' => 'Nome',
                'pes_email' => 'Email',
                'prf_action' => 'Ações'
            ))
                ->modifyCell('prf_action', function () {
                    return array('style' => 'width: 140px;');
                })
                ->means('prf_action', 'prf_id')
                ->modify('prf_action', function ($id) {
                    return ActionButton::grid([
                        'type' => 'SELECT',
                        'config' => [
                            'classButton' => 'btn-default',
                            'label' => 'Selecione'
                        ],
                        'buttons' => [
                            [
                                'classButton' => '',
                                'icon' => 'fa fa-pencil',
                                'route' => 'academico.professores.edit',
                                'parameters' => ['id' => $id],
                                'label' => 'Editar',
                                'method' => 'get'
                            ],
                            [
                                'classButton' => '',
                                'icon' => 'fa fa-eye',
                                'route' => 'academico.professores.show',
                                'parameters' => ['id' => $id],
                                'label' => 'Visualizar',
                                'method' => 'get'
                            ]
                        ]
                    ]);
                })
                ->sortable(array('prf_id', 'pes_nome'));

            $paginacao = $tableData->appends($request->except('page'));
        }

        return view('Academico::professores.index', ['tabela' => $tabela, 'paginacao' => $paginacao, 'actionButton' => $actionButtons]);
    }

    public function getCreate(Request $request)
    {
        $pessoaId = $request->get('id');

        $professor = $this->professorRepository->search(array(['prf_pes_id', '=', $pessoaId]));

        if (!$professor->isEmpty()) {
            flash()->error('Este CPF já tem um professor cadastrado!');
            return redirect()->route('academico.professores.index');
        }

        if (!is_null($pessoaId)) {
            $pessoa = $this->pessoaRepository->findById($pessoaId);

            if ($pessoa) {
                return view('Academico::professores.create', ['pessoa' => $pessoa]);
            }
        }

        return view('Academico::professores.create', ['pessoa' => []]);
    }

    public function postCreate(Request $request)
    {
        $pessoaRequest = new PessoaRequest();
        $professorRequest = new ProfessorRequest();

        try {
            $validator = Validator::make($request->all(), $pessoaRequest->rules());

            if ($validator->fails()) {
                return redirect()->back()->with('validado', true)->withInput($request->all())->withErrors($validator);
            }

            DB::beginTransaction();

            $dataPessoa = array(
                'pes_nome' => $request->input('pes_nome'),
                'pes_sexo' => $request->input('pes_sexo'),
                'pes_email' => $request->input('pes_email'),
                'pes_telefone' => $request->input('pes_telefone'),
                'pes_nascimento' => $request->input('pes_nascimento'),
                'pes_mae' => $request->input('pes_mae'),
                'pes_pai' => $request->input('pes_pai'),
                'pes_estado_civil' => $request->input('pes_estado_civil'),
                'pes_naturalidade' => $request->input('pes_naturalidade'),
                'pes_nacionalidade' => $request->input('pes_nacionalidade'),
                'pes_raca' => $request->input('pes_raca'),
                'pes_necessidade_especial' => $request->input('pes_necessidade_especial'),
                'pes_estrangeiro' => $request->input('pes_estrangeiro'),
                'pes_endereco' => $request->input('pes_endereco'),
                'pes_numero' => $request->input('pes_numero'),
                'pes_complemento' => $request->input('pes_complemento'),
                'pes_cep' => $request->input('pes_cep'),
                'pes_bairro' => $request->input('pes_bairro'),
                'pes_cidade' => $request->input('pes_cidade'),
                'pes_estado' => $request->input('pes_estado')
            );

            $cpf = $request->input('doc_conteudo');

            $dataForm = $request->all();
            $pes_id = isset($dataForm['pes_id']) ? $request->input('pes_id') : null;

            if ($pes_id) {
                if ($this->pessoaRepository->verifyEmail($request->input('pes_email'), $pes_id)) {
                    $errors = ['pes_email' => 'Email já cadastrado'];
                    return redirect()->back()->with('validado', true)->withInput($request->all())->withErrors($errors);
                }

                if ($this->documentoRepository->verifyCpf($request->input('doc_conteudo'), $pes_id)) {
                    $errors = ['doc_conteudo' => 'CPF já cadastrado'];
                    return redirect()->back()->with('validado', true)->withInput($request->all())->withErrors($errors);
                }

                $this->pessoaRepository->update($dataPessoa, $pes_id, 'pes_id');

                $dataDocumento = array(
                    'doc_tpd_id' => 2,
                    'doc_conteudo' => $cpf,
                    'doc_pes_id' => $pes_id
                );

                $this->documentoRepository->updateOrCreate(['doc_pes_id' => $pes_id, 'doc_tpd_id' => 2], $dataDocumento);
            } else {
                if ($this->pessoaRepository->verifyEmail($request->input('pes_email'))) {
                    $errors = ['pes_email' => 'Email já cadastrado'];
                    return redirect()->back()->with('validado', true)->withInput($request->all())->withErrors($errors);
                }

                if ($this->documentoRepository->verifyCpf($request->input('doc_conteudo'))) {
                    $errors = ['doc_conteudo' => 'CPF já cadastrado'];
                    return redirect()->back()->with('validado', true)->withInput($request->all())->withErrors($errors);
                }

                $pessoa = $this->pessoaRepository->create($dataPessoa);
                $pes_id = $pessoa->pes_id;

                $dataDocumento = array(
                    'doc_pes_id' => $pes_id,
                    'doc_tpd_id' => 2,
                    'doc_conteudo' => $cpf
                );

                $this->documentoRepository->create($dataDocumento);
            }

            $validator = Validator::make(['prf_pes_id' => $pes_id, 'prf_codigo' => $request->prf_codigo], $professorRequest->rules());

            if ($validator->fails()) {
                flash()->error('Professor já cadastrado!');
                return redirect()->route('academico.professores.index');
            }

            $this->professorRepository->create(['prf_pes_id' => $pes_id, 'prf_codigo' => $request->prf_codigo]);

            DB::commit();

            flash()->success('Professor criado com sucesso!');

            return redirect()->route('academico.professores.index');
        } catch (\Exception $e) {
            if (config('app.debug')) {
                throw $e;
            }
            DB::rollback();
            flash()->error('Erro ao tentar salvar. Caso o problema persista, entre em contato com o suporte.');

            return redirect()->back()->with('validado', true);
        }
    }

    public function getEdit($professorId)
    {
        $professor = $this->professorRepository->find($professorId);

        if (!$professor) {
            flash()->error('Professor não existe.');
            return redirect()->back();
        }

        $pessoa = $this->pessoaRepository->findById($professor->prf_pes_id);
        $pessoa->prf_codigo = $professor->prf_codigo;

        return view('Academico::professores.edit', ['pessoa' => $pessoa]);
    }

    public function putEdit($pessoaId, ProfessorRequest $request)
    {
        $pessoaRequest = new PessoaRequest();

        $validation = Validator::make($request->all(), $pessoaRequest->rules());

        if ($validation->fails()) {
            return redirect()->back()->withInput($request->all())->withErrors($validation->messages());
        }

        $pessoa = $this->pessoaRepository->find($pessoaId);

        if (!$pessoa) {
            flash()->error('Pessoa não existe.');
            return redirect()->route('academico.professores.index');
        }

        if ($this->pessoaRepository->verifyEmail($request->input('pes_email'), $pessoaId)) {
            $errors = ['pes_email' => 'Email já cadastrado'];
            return redirect()->back()->withInput($request->all())->withErrors($errors);
        }

        if ($this->documentoRepository->verifyCpf($request->input('doc_conteudo'), $pessoaId)) {
            $errors = ['doc_conteudo' => 'CPF já cadastrado'];
            return redirect()->back()->withInput($request->all())->withErrors($errors);
        }

        DB::beginTransaction();
        try {

            $oldPessoa = clone $pessoa;

            $pessoa->fill($request->all())->save();

            $professor = \Modulos\Academico\Models\Professor::where('prf_pes_id', $pessoaId)->first();

            if (!empty($request->prf_codigo) && $professor) {
                $professor->fill(['prf_codigo' => $request->input('prf_codigo')])->save();
            }

            $dataDocumento = [
                'doc_pes_id' => $pessoaId,
                'doc_conteudo' => $request->input('doc_conteudo'),
                'doc_tpd_id' => 2
            ];

            $this->documentoRepository->updateOrCreate(['doc_pes_id' => $pessoaId, 'doc_tpd_id' => 2], $dataDocumento);

            DB::commit();

            if ($this->checkUpdateMigracao($oldPessoa, $pessoa)) {
                $this->pessoaRepository->updatePessoaAmbientes($pessoa);
            }

            flash()->success('Professor editado com sucesso!');
            return redirect()->route('academico.professores.show', $professor->prf_id);
        } catch (ValidationException $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())->withErrors($e);
        } catch (\Exception $e) {
            if (config('app.debug')) {
                throw $e;
            }
            DB::rollback();
            flash()->error('Erro ao tentar editar. Caso o problema persista, entre em contato com o suporte.');

            return redirect()->back();
        }
    }

    public function getShow($professorId)
    {
        $professor = $this->professorRepository->find($professorId);

        if (!$professor) {
            flash()->error('Professor não existe.');
            return redirect()->back();
        }

        session(['last_acad_route' => 'academico.professores.show', 'last_id' => $professorId]);

        session(['last_acad_route' => 'academico.professores.show', 'last_id' => $professorId]);

        return view('Academico::professores.show', ['pessoa' => $professor->pessoa]);
    }

    private function checkUpdateMigracao($oldPessoa, $pessoa)
    {
        if (strcmp($oldPessoa->pes_nome, $pessoa->pes_nome) != 0 || strcmp($oldPessoa->pes_email, $pessoa->pes_email) != 0
            || strcmp($oldPessoa->pes_cidade, $pessoa->pes_cidade) != 0) {
            return true;
        }

        return false;
    }
}
