@extends('layouts.app')
<meta name="csrf_token" content="{{ csrf_token() }}" />
@section('content')
    @include('componentes.navbar')
    

    <form name="addItem" method="POST" class="list">
        @csrf

        <label id="count_itens" for="count_itens"></label>
        @foreach ($produtos as $produto)
            <a class="listHome" style="cursor: pointer">
                <ul class="list-group">
                    <li class="list-group-item active">
                        <div class="listCar">
                            <h6>{{ $produto->nome }}</h6>

                            <button type="submit" onclick="cli(<?php echo $produto->id; ?>)" class="buttonAdd"><img
                                    class="imgCarr" src="{{ asset('addCar.ico') }}" alt=""></button>
                        </div>
                    </li>
                    <li class="list-group-item">
                        <div class="listCar">
                            <h6> Preço :</h6>
                            <h4>R$ {{ reais($produto->preco) }}</h4>
                        </div>
                    </li>
                </ul>
            </a>
            {{-- Modal --}}

            {{-- Fim Desconto Modal --}}
        @endforeach
    </form>


    {{-- paginação --}}
    <nav aria-label="Navegação de página exemplo">
        <ul class="pagination">
            <li class="page-item">
                <a class="page-link" href="{{ $produtos->previousPageUrl() }}" aria-label="Anterior">
                    <span aria-hidden="true">&laquo;</span>
                    <span class="sr-only">Anterior</span>
                </a>
            </li>

            @for ($i = 1; $i <= 3; $i++)
                <!-- a Tag for another page -->
                <li class="page-item"><a class="page-link"
                        href="{{ $produtos->url($i) }}">{{ $i }}</a></li>
            @endfor

            <li class="page-item">
                <a class="page-link" href="{{ $produtos->nextPageUrl() }}" aria-label="Próximo">
                    <span aria-hidden="true">&raquo;</span>
                    <span class="sr-only">Próximo</span>
                </a>
            </li>
        </ul>
    </nav>
@endsection
<script type="text/javascript" src="{{ asset('js/viewhome.js') }}" defer></script>
