<?php

namespace Zeropingheroes\Lanager\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Zeropingheroes\Lanager\Page;
use Illuminate\Http\Request;
use Zeropingheroes\Lanager\Requests\StorePageRequest;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        return View::make('pages.page.index')
            ->with('pages', Page::visible()->get());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return View::make('pages.page.create')
            ->with('page', new Page);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $httpRequest
     * @return \Illuminate\Http\Response
     * @internal param Request|StoreRoleAssignmentRequest $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $httpRequest)
    {
        $this->authorize('create', Page::class);

        $input = [
            'title' => $httpRequest->input('title'),
            'content' => $httpRequest->input('content'),
            'published' => $httpRequest->has('published'),
        ];

        $request = new StorePageRequest($input);

        if ($request->invalid()) {
            return redirect()
                ->back()
                ->withError($request->errors())
                ->withInput();
        }
        $page = Page::create($input);

        return redirect()
            ->route('pages.index')
            ->withSuccess(__('phrase.page-successfully-created', ['title' => $page->title]));
    }

    /**
     * Display the specified resource.
     *
     * @param  \Zeropingheroes\Lanager\Page $page
     * @param string $slug
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Page $page, $slug = '')
    {
        $page = Page::visible()->findOrFail($page->id);

        // If the page is accessed without the URL slug
        // redirect to it
        if (!$slug) {
            return redirect()->route('pages.show', ['id' => $page->id, 'slug' => str_slug($page->title)]);
        }

        return View::make('pages.page.show')
            ->with('page', $page);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Zeropingheroes\Lanager\Page $page
     * @return \Illuminate\Contracts\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(Page $page)
    {
        $this->authorize('update', $page);

        return View::make('pages.page.edit')
            ->with('page', $page);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $httpRequest
     * @param  \Zeropingheroes\Lanager\Page $page
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $httpRequest, Page $page)
    {
        $this->authorize('update', $page);

        $input = [
            'title' => $httpRequest->input('title'),
            'content' => $httpRequest->input('content'),
            'published' => $httpRequest->has('published'),
            'id' => $page->id,
        ];

        $request = new StorePageRequest($input);

        if ($request->invalid()) {
            return redirect()
                ->back()
                ->withError($request->errors())
                ->withInput();
        }
        $page->update($input);

        return redirect()
            ->route('pages.show', $page)
            ->withSuccess(__('phrase.page-successfully-updated', ['title' => $page->title]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Zeropingheroes\Lanager\Page $page
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Page $page)
    {
        $this->authorize('delete', $page);

        Page::destroy($page->id);

        return redirect()
            ->route('pages.index')
            ->withSuccess(__('phrase.page-successfully-deleted', ['title' => $page->title]));
    }
}