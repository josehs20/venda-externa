<?php

namespace App\Http\Controllers;

use App\Models\Carrinho;
use App\Models\CarrinhoItem;
use App\Models\Cliente;
use App\Models\Produto;
use App\Models\VendedorCliente;
use Facade\FlareClient\Http\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Laravel\Ui\Presets\React;

class VendaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $produtos = Produto::where('loja_id', auth()->user()->loja_id)->where('situacao', 'A')->whereRaw("nome like '%{$request->nome}%'")->orderBy('nome')->paginate(20);

        return view('home', compact('produtos',));
    }

    public function busca_produto_ajax()
    {
        $dados['busca'] = Produto::where('loja_id', auth()->user()->loja_id)->where('situacao', 'A')->whereRaw("nome like '%{$_GET['busca']}%'")->orderBy('nome')->paginate(20);

        echo  json_encode($dados);
    }

    public function itens_carrinho($user_id = null, $msg = null)
    {
        // dd($msg);
        $clientes_user = Cliente::with('infoCliente')->where('loja_id', auth()->user()->loja_id)->orderBy('nome')->paginate(50);
        // dd($clientes_user);
        $itens = Carrinho::with('carItem')->where('user_id', $user_id)->where('status', 'Aberto')->first();

        $valor_itens_total_sem_desconto = $itens ? $itens->carItem()->selectRaw("sum(preco * quantidade) total")->where('carrinho_id', $itens->id)->first() : null;
        $valor_itens_desconto = $itens ? $itens->total : null;

        $total_desconto_valor = $itens ? $itens->valor_desconto : null;
        $tp_desconto = $itens ? $itens->tp_desconto_unificado : null;

        if ($msg == 'deletado') {
            Session::flash('item_deletado_carrinho');
        } elseif ($msg == 'alterado') {
            Session::flash('item_alterado_carrinho');
        } elseif ($msg == 'unificado') {
            Session::flash('item_unificado_carrinho');
        } elseif ($msg == 'zerado') {
            Session::flash('item_zerado_carrinho');
        } elseif ($msg == 'quantidade_alterada') {
            Session::flash('quantidade_alterada');
        }

        return view('itemCarrinho', compact('itens', 'clientes_user', 'valor_itens_total_sem_desconto', 'total_desconto_valor', 'valor_itens_desconto', 'tp_desconto',));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store() //metodo usado pelo ajax
    {
        $carrinho = Carrinho::where('user_id', auth()->user()->id)->where('status', 'Aberto')->first();
        $produto = Produto::find($_POST['id']);
        $item = $carrinho ? CarrinhoItem::where('carrinho_id', $carrinho->id)->where('produto_id', $produto->id)->first() : null;

        //caso carrinho não tenha nenhum carrinho aberto já é aberto automaticamente
        if (!$carrinho) {
            $carrinho = new Carrinho();
            $carrinho->user_id = auth()->user()->id;
            $carrinho->status = 'Aberto';
            $carrinho->save();

            $item = new CarrinhoItem();
            $this->add_item($item, $produto, $carrinho);

            $this->atualiza_Carrinho_desconto_unico($carrinho);

            $count_item = Carrinho::with('carItem')->where('user_id', auth()->user()->id)
                ->where('status', 'Aberto')->first();

            $dado['count_item'] = $count_item->carItem->count();
            $dado['produto_adicionado'] = $produto->nome;
            $dado['ok'] = true;
            echo json_encode($dado);
            return;
        } elseif (!$item) {

            $item = new CarrinhoItem();
            $this->add_item($item, $produto, $carrinho);

            // atualização de desconto unico ou sem desconto para itens diferentes que são inseridos no carrinho
            $this->atualiza_Carrinho_desconto_unico($carrinho);

            //conta quantidade no carrinho ajax
            $count_item = Carrinho::with('carItem')->where('user_id', auth()->user()->id)
                ->where('status', 'Aberto')->first();

            $dado['count_item'] = $count_item->carItem->count();
            $dado['produto_adicionado'] = $produto->nome;
            $dado['ok'] = true;
            echo json_encode($dado);

            return;
        } else {
            $quantidade = $item->quantidade + 1;

            if ($item->tipo_desconto) {

                $this->atualiza_Carrinho_desconto_parcial($item, $item->qtd_desconto, $quantidade);

                $dado['produto_adicionado'] = $produto->nome;
                $dado['ok'] = "add";
                echo json_encode($dado);
            } else {
                $item->update([
                    'quantidade' => $quantidade,
                    'valor' => $item->preco * $quantidade,
                ]);

                $this->atualiza_Carrinho_desconto_unico($carrinho);

                $dado['produto_adicionado'] = $produto->nome;
                $dado['ok'] = "add";
                echo json_encode($dado);
            }
        }
    }

    public function add_item($item, $produto, $carrinho)
    {
        $item->produto_id     = $produto->id;
        $item->carrinho_id    = !$carrinho ? $carrinho->id : $carrinho->id;
        $item->alltech_id     = $produto->alltech_id;
        $item->preco          = $produto->preco;
        $item->quantidade     = 1;
        $item->valor          = $produto->preco;
        $item->save();
    }
    public function verifica_custo_venda($produto, $desconto_final, $quantidade)
    {
        $custo = ($produto->custo * $quantidade);
        $preco_final = ($produto->preco * $quantidade) - $desconto_final;

        if (($preco_final - $custo) <= 0) {

            return false;
        } else {
            return true;
        }
    }

    public function unifica_valor_Itens(Request $request, $carrinho)
    {
        $carrinho = Carrinho::with('carItem')->find($carrinho);
        // dd($request->all());
        foreach ($carrinho->carItem as $item) {
            $item->update([
                'tipo_desconto' => null,
                'qtd_desconto' => null,
                'valor_desconto' => null,
                'valor' => $item->preco * $item->quantidade,
            ]);
            $valor_itens_bruto[] = $item->valor;
        }
        $desconto_final = $request->tipo_unificado == 'porcento' ? ($request->qtd_unificado / 100) * (array_sum($valor_itens_bruto)) : $request->qtd_desconto;
        $valor_final_item = array_sum($valor_itens_bruto) - $desconto_final;
        //  dd($desconto_final);
        $carrinho->update([
            'desconto_qtd' => $request->qtd_unificado,
            'tp_desconto' => $request->tipo_unificado == 'porcento' ? 'porcento_unico' : 'dinheiro_unico',
            'valor_desconto' => $desconto_final,
            'valor_bruto' => array_sum($valor_itens_bruto),
            'total' => $valor_final_item,
        ]);

        return redirect(route('itens_carrinho', ['user_id' => auth()->user()->id, 'msg' => 'unificado']));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($venda)
    {
        //dd($venda);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $venda)
    {
        //id do item da venda

        $item = CarrinhoItem::find($venda);

        if ($request->quantidade && !$request->tipo_desconto) {
            $carrinho = Carrinho::find($item->carrinho_id);

            // dd($item);
            $item->update([
                'quantidade' => $request->quantidade,
                'valor' => $item->preco * $request->quantidade,
            ]);

            $this->atualiza_Carrinho_desconto_unico($carrinho);
            return redirect(route('itens_carrinho', ['user_id' => auth()->user()->id, 'msg' => 'quantidade_alterada']));
        } else {

            $item->update([
                'tipo_desconto' => $request->tipo_desconto == 'porcento' ? 'porcento' : 'dinheiro'
            ]);

            $this->atualiza_Carrinho_desconto_parcial($item, $request->qtd_desconto, $request->quantidade);

            return redirect(route('itens_carrinho', ['user_id' => auth()->user()->id, 'msg' => 'alterado']));
        }
    }
    public function zeraDesconto($carrinho)
    {
        $carrinho = Carrinho::with('carITem')->find($carrinho);

        foreach ($carrinho->carItem as $key => $item) {
            $item->update([
                'tipo_desconto' => null,
                'qtd_desconto' => null,
                'valor_desconto' => null,
                'valor' => $item->preco * $item->quantidade,
            ]);
            $valor_itens[] = $item->valor;
        }
        //dd(array_sum($valor_itens));
        $carrinho->update([
            'desconto_qtd' => null,
            'tp_desconto' => null,
            'valor_desconto' => null,
            'total' => array_sum($valor_itens),
        ]);

        return redirect(route('itens_carrinho', ['user_id' => auth()->user()->id, 'msg' => 'zerado']));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($venda)
    {
        Carrinho::find($venda)->delete();

        Session::flash('cancelar_carrinho');

        return redirect(route('venda.index'));
    }
    public function destroyItem($item)
    {
        $carrinho = Carrinho::where('user_id', auth()->user()->id)->where('status', 'Aberto')->first();

        if ($carrinho->carItem()->count() === 1) {
            $carrinho->delete();
            return redirect(route('venda.index'));
        } else {
            if (CarrinhoItem::find($item)) {
                CarrinhoItem::find($item)->delete();
                $this->atualiza_Carrinho_desconto_unico($carrinho);

                return redirect(route('itens_carrinho', ['user_id' => auth()->user()->id, 'msg' => 'deletado']));
            } else {
                return redirect(route('itens_carrinho', auth()->user()->id));
            }
        }
    }

    public function salvar_venda(Request $request)
    {

        $carrinho_aberto =     Carrinho::where('user_id', auth()->user()->id)->where('status', 'Aberto')->first();

        if ($request->cliente_id) {
            if ($request->salvaSubstitui) {

                $carrinho_aberto->update([
                    'status' => 'Salvo',
                    'cliente_id' => $carrinho_aberto->cliente_id ? $carrinho_aberto->cliente_id : $request->cliente_id,
                ]);

                Carrinho::find($request->salvaSubstitui)->update([
                    'status' => 'Aberto',
                ]);
            }

            $carrinho_aberto->update([
                'status' => 'Salvo',
                'cliente_id' => $carrinho_aberto->cliente_id ? $carrinho_aberto->cliente_id : $request->cliente_id,
            ]);
        }
        Session::flash('carrinho_salvo');
        return redirect(route('itens_carrinho'));
    }

    public function busca_cliente_ajax()
    {
        if ($_GET['nome']) {
            $dado['result'] = Cliente::where('loja_id', auth()->user()->loja_id)->whereRaw("nome like '%{$_GET['nome']}%'")->paginate(20);
            //$dado['result'] = $_GET['nome'];
            echo json_encode($dado);
        }
    }
    public function atualiza_Carrinho_desconto_parcial($item, $quantidade_desconto, $quantidade)
    {

        $desconto_final = $item->tipo_desconto == 'porcento' ? ($quantidade_desconto / 100) * ($quantidade * $item->preco) : $quantidade_desconto;
        $valor_final_item = ($quantidade * $item->preco) - $desconto_final;
        //dd($desconto_final);
        $item->update([
            'quantidade' => $quantidade,
            'qtd_desconto' => $quantidade_desconto,
            'valor_desconto' => $desconto_final,
            'valor'          => $valor_final_item,
        ]);

        $itens = CarrinhoItem::where('carrinho_id', $item->carrinho_id)->get();

        foreach ($itens as $item) {
            // dd($item->valor_desconto);
            $valor_itens_desconto[] = $item->valor_desconto;
            $valor_itens_bruto[] = $item->preco * $item->quantidade;
            $valor_itens[] = $item->valor;
        }

        Carrinho::find($item->carrinho_id)->update([
            'tp_desconto' => 'parcial',
            'valor_desconto' => array_sum($valor_itens_desconto),
            'valor_bruto' => array_sum($valor_itens_bruto),
            'total' => array_sum($valor_itens),
        ]);
    }
    public function atualiza_Carrinho_desconto_unico($carrinho)
    {
        $itens = $carrinho->carItem()->get();

        foreach ($itens as $item) {
            $valor_itens_desconto[] = $item->valor_desconto;
            $valor_itens_bruto[] = $item->preco * $item->quantidade;
            $valor_itens[] = $item->valor;
        }
        if ($carrinho->tp_desconto == 'porcento_unico' or $carrinho->tp_desconto == 'dinheiro_unico') {
            $desconto_final = $carrinho->tp_desconto == 'porcento_unico' ? ($carrinho->desconto_qtd / 100) * (array_sum($valor_itens)) : $carrinho->desconto_qtd;
            $valor_final_item = array_sum($valor_itens) - $desconto_final;

            $carrinho->update([
                'valor_desconto' => $desconto_final,
                'valor_bruto' => array_sum($valor_itens),
                'total' => $valor_final_item,
            ]);
            return;
        }

        $carrinho->update([
            'valor_desconto' => array_sum($valor_itens_desconto),
            'valor_bruto' => array_sum($valor_itens_bruto),
            'total' => array_sum($valor_itens),
        ]);
    }
}
