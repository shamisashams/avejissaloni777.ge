<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Repositories\Eloquent\ProductRepository;
use Spatie\TranslationLoader\TranslationLoaders\Db;

class ProductController extends Controller
{

    protected $productRepository;

    public function __construct(ProductRepository $productRepository){
        $this->productRepository = $productRepository;
    }

    /**
     * @param string $locale
     * @param Request $request
     * @return Application|Factory|View
     */
    public function index(string $locale, Request $request)
    {
        $page = Page::where('key', 'products')->firstOrFail();
        $products = Product::with(['files'])->paginate(4);

        $images = [];
        foreach ($page->sections as $sections){
            if($sections->file){
                $images[] = asset($sections->file->getFileUrlAttribute());
            } else {
                $images[] = null;
            }

        }

        //dd($products);
        return Inertia::render('Products/Products',[
            'products' => $products,
            'images' => $images
        ]);
    }


    /**
     * @param string $locale
     * @param string $slug
     * @return Application|Factory|View
     */
    public function show(string $locale, string $slug)
    {
        //\Illuminate\Support\Facades\DB::enableQueryLog();

        $product = Product::where(['status' => true, 'slug' => $slug])->whereHas('categories', function (Builder $query) {
            $query->where('status', 1);

        })->with(['files','categories'])->firstOrFail();

        //dd(last($product->categories));
        $categories = $product->categories;


        $path = [];
        foreach ($categories as $key =>$item){

            if($item->isLeaf()){

                $ancestors = $item->ancestors;

                $k = 0;
                foreach ($ancestors as $ancestor){
                    $path[$k]['id'] = $ancestor->id;
                    $path[$k]['slug'] = $ancestor->slug;
                    $path[$k]['title'] = $ancestor->title;
                    $k++;
                }

                $path[$k]['id'] = $item->id;
                $path[$k]['slug'] = $item->slug;
                $path[$k]['title'] = $item->title;
                break;
            } else {
                $ancestors = $item->ancestors;
                $k = 0;
                foreach ($ancestors as $ancestor){
                    $path[$k]['id'] = $ancestor->id;
                    $path[$k]['slug'] = $ancestor->slug;
                    $path[$k]['title'] = $ancestor->title;
                    $k++;
                }
                $path[$k]['id'] = $item->id;
                $path[$k]['slug'] = $item->slug;
                $path[$k]['title'] = $item->title;
            }

        }
        //dd($path);
        $similar_products = Product::where(['status' => 1, 'product_categories.category_id' => $path[0]['id']])
            ->where('products.id','!=',$product->id)
            ->leftJoin('product_categories', 'product_categories.product_id', '=', 'products.id')
            ->inRandomOrder()
            ->with('files')
            ->paginate(8);
        //dd($category);
        //$result = [];
        //$result['id'] = $category[0]['id'];
        //$result['title'] = $category[0]['title'];
        //dd(\Illuminate\Support\Facades\DB::getQueryLog());

        /*return view('client.pages.product.show', [
            'product' => $product
        ]);*/
        return Inertia::render('SingleProduct/SingleProduct',[
            'product' => $product,
            'category_path' => $path,
            'similar_products' => $similar_products
        ]);
    }

}
