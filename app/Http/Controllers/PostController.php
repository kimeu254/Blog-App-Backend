<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Post;

class PostController extends Controller
{
    //
    public function index()
    {
        return response([
            'posts' => Post::orderBy('created_at', 'desc')
                ->with('user:id,name,image')
                ->withCount('comments', 'likes')
                ->with('likes', function($like){
                    return $like->where('user_id', auth()->user()->id)
                        ->select('id', 'user_id', 'post_id')
                        ->get();
                })
                ->get()
        ], 200);
    }

    public function show($id)
    {
        return response([
            'post' => Post::where('id', $id)->withCount('comments', 'likes')->get()
        ], 200);
    }

    public function store(Request $request)
    {
        //validate fields
        $attrs = $request->validate([
            'body' => 'required|string'
        ]);

        $image = $this->saveImage($request->image, 'posts');

        $post = Post::create([
            'body' => $attrs['body'],
            'user_id' => auth()->user()->id,
            'image' => $image
        ]);

        // for now let's skip image post

        return response([
            'message' => 'Post created',
            'post' => $post
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $post = Post::find($id);

        if(!$post)
        {
            return response([
                'message' => 'Post not found'
            ], 403);
        }

        if($post->user_id != auth()->user()->id)
        {
            return response([
                'message' => 'Permission denied'
            ]);
        }
        
        //validate fields
        $attrs = $request->validate([
            'body' => 'required|string'
        ]);

        $post->update([
            'body' => $attrs['body'],
        ]);

        // for now let's skip image post

        return response([
            'message' => 'Post created',
            'post' => $post
        ], 200);
    }

    public function destroy($id)
    {
        $post = Post::find($id);

        if(!$post)
        {
            return response([
                'message' => 'Post not found'
            ], 403);
        }

        if($post->user_id != auth()->user()->id)
        {
            return response([
                'message' => 'Permission denied'
            ]);
        }

        $post->comments()->delete();
        $post->likes()->delete();
        $post->delete();

        return response([
            'message' => 'Post deleted.',
        ], 200);
    }
}
