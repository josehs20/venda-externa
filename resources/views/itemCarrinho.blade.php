@extends('layouts.app')

@section('content')
    <style>
        .card-box {
            position: relative;
            color: #fff;
            padding: 20px 10px 40px;
            margin: 20px 0px;
        }

        .card-box:hover {
            text-decoration: none;
            color: #f1f1f1;
        }

        .card-box:hover .icon i {
            font-size: 100px;
            transition: 1s;
            -webkit-transition: 1s;
        }

        .card-box .inner {
            padding: 5px 10px 0 10px;
        }

        .card-box h3 {
            font-size: 27px;
            font-weight: bold;
            margin: 0 0 8px 0;
            white-space: nowrap;
            padding: 0;
            text-align: left;
        }

        .card-box p {
            font-size: 15px;
        }

        .card-box .icon {
            position: absolute;
            top: auto;
            bottom: 5px;
            right: 5px;
            z-index: 0;
            font-size: 72px;
            color: rgba(0, 0, 0, 0.15);
        }

        .card-box .card-box-footer {
            position: absolute;
            left: 0px;
            bottom: 0px;
            text-align: center;
            padding: 3px 0;
            color: rgba(255, 255, 255, 0.8);
            background: rgba(0, 0, 0, 0.1);
            width: 100%;
            text-decoration: none;
        }

        .card-box:hover .card-box-footer {
            background: rgba(0, 0, 0, 0.3);
        }

        .bg-blue {
            background-color: #00c0ef !important;
        }

        .bg-green {
            background-color: #00a65a !important;
        }

        .bg-orange {
            background-color: #f39c12 !important;
        }

        .bg-red {
            background-color: #d9534f !important;
        }

    </style>


    @include('componentes.navbar')
   
    {{-- cards iniciais --}}
    <div class="container">
        <div class="row">

            <div class="col-lg-3 col-sm-6">
                <div class="card-box bg-green">
                    <div class="inner">
                        <i class="bi bi-cash-coin">&ensp;185358</i>

                    </div>
                    &ensp;
                    <div class="icon">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <a href="#" class="card-box-footer">Valor Total<i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card-box bg-orange">
                    <div class="inner">
                        <i class="bi bi-percent">&ensp; 5464 </i>
                    </div>
                    <div class="inner">
                        <i class="bi bi-cash-stack">&ensp; 5464 </i>
                    </div>
                    <div class="icon">
                        <i class="bi bi-percent"></i>
                    </div>
                    <a href="#" class="card-box-footer">Total De Desconto<i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>

        </div>
    </div>
    {{-- Fim cards iniciais --}}
    <hr><br>
    {{-- tabela de itens --}}
    @if ($itens)
   

        <div class="container">
            @foreach ($itens->carItem as $item)
                <ul class="list-group">
                    <li class="list-group-item active" aria-current="true">{{ $item->nome }}</li>
                
                    <li class="list-group-item">
                        <div class="listCar">
                            <p> Preço </p>
                            <span>{{ $item->preco }}</span>
                        </div>
                    </li>

                    <li class="list-group-item">
                        <div class="listCar">
                            <p> Quantidade </p>
                            <span>{{ $item->quantidade }}</span>
                        </div>
                    </li>

                    <li class="list-group-item">
                        <div class="listCar">
                            <p> Desconto </p>
                            <span>{{ !$item->qtd_desconto? '': ($item->tipo_desconto == 'Porcentagem'? '%' . $item->qtd_desconto: "R$" . $item->qtd_desconto) }}</span>
                        </div>
                    </li>
                </ul>
                <br>
            @endforeach
        </div>
    @else
        <div class="alert alert-warning" role="alert">
            Adicione Produtos No Seu Carrinho De Vendas :)
        </div>
    @endif
    {{-- Fim tabela de itens --}}
@endsection
