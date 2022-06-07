@extends('layouts.app')


@section('content')

    @include('componentes.navbar', ['titulo' => 'Em espera'])
    <br><br><br><br><br>
    @if (!count($carrinhos))
        <div class="alert alert-warning mt-5" role="alert">
            Nenhuma venda em espera !
        </div>
    @else
        <div class="listCliente">
            <div class="container">

                @foreach ($carrinhos as $c)
                    <ul class="list-group mt-3">
                        <button class="collapsible" data-bs-toggle="modal"
                            data-bs-target="#modalVendaFinalizada{{ $c->id }}"
                            style="background-color: rgb(58, 36, 252); font-size:16px;border-radius:7px;cursor: pointer; white-space: nowrap; overflow:hidden">
                            <h6 style="margin-top:-10px; margin-left: 10px;">
                                {{ $c->cliente ? $c->cliente->nome : 'Cliente Não Informado' }}
                            </h6>
                        </button>

                        <!-- Modal Venda Salva-->
                        <div class="modal fade" id="modalVendaFinalizada{{ $c->id }}" data-bs-backdrop="static"
                            data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="staticBackdropLabel">Itens Da Venda
                                        </h5>

                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <h6 class="col-6">Valor Bruto: R$
                                                <b>{{ reais($c->valor_bruto) }}</b>
                                            </h6>
                                            <h6 class="col-6">Descontos Totais: R$
                                                <b>{{ reais($c->valor_desconto + $c->valor_desconto_sb_venda) }}</b>
                                            </h6>
                                        </div>
                                        <br>

                                        <div class="row">

                                            <p style="color:black;">
                                                &ensp;Tipo de
                                                desconto:{{ $c->tp_desconto == 'porcento_unico' ? ' Único' : ($c->tp_desconto == 'dinheiro_unico' ? ' Único' : (!$c->tp_desconto ? ' Nenhum Desconto Aplicado' : ' Parcial')) }}
                                            </p>

                                        </div>
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th colspan="2">Nome</th>
                                                    <th>Preço</th>
                                                    <th>Qtd.</th>
                                                    @if ($c->tp_desconto == 'parcial')
                                                        <th>Desc</th>
                                                    @endif
                                                    <th>valor</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($c->carItem as $item)
                                                    <tr>
                                                        <td colspan="2">
                                                            {{ substr($item->produto['nome'], 0, 25) }}&ensp;{{ $item->iGrade ? '/' . $item->iGrade->tam : '' }}
                                                        </td>
                                                        <th>{{ reais($item->preco) }}</th>
                                                        <th>{{ $item->quantidade }}</th>
                                                        @if ($c->tp_desconto == 'parcial')
                                                            <th>{{ $item->valor_desconto ? reais($item->valor_desconto) : '0' }}
                                                            </th>
                                                        @endif
                                                        <th>{{ reais($item->preco * $item->quantidade) }}</th>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <br>

                                    <div class="row">
                                        &ensp; <h6 class="col-5">Total: R$
                                            <b>{{ reais($c->total) }}</b>
                                        </h6>
                                        <h6 class="col-6">Status:
                                            <b>{{ $c->status == 'descInvalido' ? ' Em Espera' : ($c->status == 'aprovada' ? 'Aprovada' : 'Recusada') }}</b>
                                        </h6>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                                            aria-label="Close">Sair</button>


                                        @if ($c->status == 'aprovada')
                                            <form method="GET"
                                                action="{{ route('venda_aprovada', ['carrinho' => $c->id]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-success">prosseguir</button>
                                            </form>
                                        @elseif($c->status == 'recusada')
                                            <form action="{{ route('venda.destroy', ['venda' => $c->id]) }}"
                                                method="POST" class="modal-footer">
                                                @method('DELETE')
                                                @csrf
                                                <button type="submit" class="btn btn-danger">Deletar</button>
                                            </form>
                                        @endif

                                    </div>

                                </div>
                            </div>
                        </div>
                    </ul>
                @endforeach
            </div>

        </div>
    @endif

@endsection