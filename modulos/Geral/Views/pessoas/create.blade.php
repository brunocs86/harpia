@extends('layouts.modulos.geral')

@section('title')
    Pessoas
@stop

@section('stylesheets')
    <link rel="stylesheet" href="{{ asset('/css/plugins/datepicker3.css') }}">
@stop

@section('subtitle')
    Cadastro de pessoas
@stop

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Formulário de cadastro de pessoas</h3>
        </div>
        <div class="box-body">
            {!! Form::open(["url" => url('/') . "/geral/pessoas/create", "method" => "POST", "id" => "form", "role" => "form"]) !!}
                @include('Geral::pessoas.includes.formulario')

                <div class="row">
                    <div class="form-group col-md-12">
                        {!! Form::submit('Salvar dados', ['class' => 'btn btn-primary pull-right']) !!}
                    </div>
                </div>
            {!! Form::close() !!}
        </div>
    </div>
@stop

@section('scripts')
    <script src="{{ asset('/js/plugins/input-mask/inputmask.js') }}"></script>
    <script src="{{ asset('/js/plugins/input-mask/jquery.inputmask.js') }}"></script>
    <script src="{{asset('/js/plugins/bootstrap-datepicker.js')}}"></script>
    <script src="{{asset('/js/plugins/bootstrap-datepicker.pt-BR.js')}}"></script>
    <script src="{{ asset('/js/plugins/cpfcnpj.min.js') }}"></script>

    <script>

        $(function (){
            $('#doc_conteudo').inputmask({"mask": "999.999.999-99", "removeMaskOnSubmit": true});
            $('#pes_telefone').inputmask({"mask": "(99) 99999-9999", "removeMaskOnSubmit": true});
            $('.datepicker').datepicker({
                format: 'dd/mm/yyyy',
                language: 'pt-BR',
                autoclose: true
            });
        });
    </script>
@endsection