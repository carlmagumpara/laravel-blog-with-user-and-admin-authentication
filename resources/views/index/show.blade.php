@extends('layouts.app')

@section('content')
<div class="post-header flex-center" style="background-image: url('/uploads/post-header/{{$post->post_image}}');">
  <div class="blur">
    <div class="row">
      <div class="col-md-10 col-md-offset-1">
        <h1 class="white text-left title-header" style="margin-top: 100px;">{{$post->title}}</h1>
      </div>
    </div>
  </div>
</div>
<span post-data-id="{{$post->id}}" class="post-data hide"></span>
<div class="container">
  <div class="row">
    <div class="col-md-8 col-md-offset-2">
      <h3>{{$post->title}}</h3>
      <p><small>Posted by: {{$post->author->name}}</small></p>
      <p><small>Posted on: {{ date('F d, Y', strtotime($post->created_at)) }}</small></p>
      {!! $post->body !!}
      <p><span class="fui-tag"></span> <small> Tags: </small>
      @for ($i = 0; $i < count(json_decode($post->tags)); $i++)
        <span class="badge"> 
          <a href="/posts/tag/?tag={{json_decode($post->tags)[$i]}}" class="tags">{{ json_decode($post->tags)[$i]}}</a>
        </span>
      @endfor
      </p>
      @if (Auth::user())
        <div class="like-buttons">
          <span class="like-count badge">
            @{{ likes.length }}
          </span>
          <div class="inline" v-if="checkIfItIsInArray(likes)">
            <form v-bind:action="getLikeIdUrl(likes)" method="POST" class="inline unlike-form" @submit.prevent="UnLike(likes, {{ $post->id }} , {{ Auth::user()->id }})">
              {{ csrf_field() }}
              {{ method_field('DELETE') }}
              <button type="submit" class="btn btn-link btn-sm">
                <i class="fa fa-thumbs-o-down" aria-hidden="true"></i> Unlike
              </button>
            </form>
          </div>
          <div class="inline" v-else>
            <form action="/post/like" method="POST" class="inline like-form" @submit.prevent="Like">
              {{ csrf_field() }}
              <input type="hidden" name="post_id" value="{{ $post->id }}">
              <button type="submit" class="btn btn-link btn-sm">
                <i class="fa fa-thumbs-o-up" aria-hidden="true"></i> Like
              </button>
            </form>
          </div>
          <p class="like-this no-padding" v-for="like in likes"> 
            <i class="fa fa-thumbs-o-up" aria-hidden="true"></i>
            @{{like.firstname}} @{{like.lastname}} like this post         
          </p>
        </div>
      @endif
    </div>
  </div>
  <div class="row">
    <div class="col-md-8 col-md-offset-2 comments">
      <div class="row">
        <div class="col-md-10">
          @if (Auth::user())
            <h6><span class="fui-new"></span> Post a comment:</h6>
            <form action="/comment/store" method="POST" class="comment-form" @submit.prevent="postComment">
              {{ csrf_field() }}
              <input type="hidden" name="post_id" value="{{$post->id}}">
              <div class="form-group">
                <textarea rows="3" class="form-control" name="comment" placeholder="Comment..."></textarea>
              </div>
              <div class="form-group text-right">
                  <button type="submit" class="btn btn-inverse"> <i class="fa fa-paper-plane" aria-hidden="true"></i> Submit Comment</button>
              </div>
            </form>
          @else
            <div class="center">
              <h6 class="no-padding center">Want to comment to this post?</h6>
              <a href="/login" class="btn btn-info center"> Login! </a>
            </div>
          @endif
        </div>
      </div>
      <div class="row">
        <div class="col-md-10">
          <h5><span class="fui-chat"></span>&nbsp;&nbsp;@{{ comments.length }}
          Comment<span v-if="comments.length > 1">'s</span>:</h5>
          <hr>
          <div class="comment-list" v-for="comment in comments" v-bind:id="comment.id" >
            <article class="row comment-item">
              <div class="col-md-2 col-sm-2 hidden-xs">
                <figure class="thumbnail">
                  <img class="img-responsive" v-bind:src="getAvatar(comment.avatar)">
                </figure>
              </div>
              <div class="col-md-10 col-sm-10">
                <div class="panel panel-default arrow left">
                  <div class="panel-body">
                    @if (Auth::user())
                      <div class="btn-group pull-right" v-if="comment.user_id == {{ Auth::user()->id }}">
                        <button data-toggle="dropdown" class="btn btn-link dropdown-toggle" type="button"><span class="fui-triangle-down-small"></span></button>
                        <ul role="menu" class="dropdown-menu">
                          <li><a href="#" @click.prevent="deleteComment(comment.id, comment)">Delete</a></li>
                        </ul>
                      </div>
                    @endif
                    <header class="text-left">
                      <div class="comment-user"><span class="fui-user"></span>&nbsp;&nbsp;
                      <a href="#" @click.prevent="showUsersInfo(comment.user_id)"> 
                        @{{ comment.firstname }} @{{ comment.lastname }}
                      </a>
                      </div>
                      <small>
                        <time class="comment-date like-this no-padding" datetime="16-12-2014 01:05" v-if="comment.created_at['date']"><i class="fa fa-clock-o"></i>&nbsp;&nbsp; @{{getTime(comment.created_at['date'])}}</time>
                        <time class="comment-date like-this no-padding" datetime="16-12-2014 01:05" v-else><i class="fa fa-clock-o"></i>&nbsp;&nbsp; @{{getTime(comment.created_at)}}</time>
                      </small>
                    </header>
                    <div class="comment-post">
                      <p>
                        @{{ comment.comment }}
                      </p>
                    </div>
                    <div class="text-right">
                      <a @click.prevent="showReply(comment.id)" href="#" class="btn btn-default btn-xs">
                      <i class="fa fa-reply"></i> reply</a>
                    </div>
                  </div>
                </div>
              </div>
            </article>
            <div v-bind:class="replyForm(comment.id)" class="reply-hide">
              @if (Auth::user())
                <article class="row">
                  <div class="col-md-2 col-sm-2 col-md-offset-1 col-sm-offset-0 hidden-xs">
                    <figure class="thumbnail">
                      <img class="img-responsive" v-bind:src="getAvatar('{{Auth::user()->avatar}}')">
                    </figure>
                  </div>
                  <div class="col-md-9 col-sm-9">
                    <form action="/reply/store" method="POST" @submit.prevent="postReply(comment.id)">
                      {{ csrf_field() }}
                      <input type="hidden" name="post_id" value="{{$post->id}}">
                      <input type="hidden" name="comment_id" v-bind:value="comment.id">
                      <div class="form-group">
                        <textarea rows="3" class="form-control" name="reply" placeholder="Reply..."></textarea>
                      </div>
                      <div class="form-group text-right">
                          <button type="submit" class="btn btn-primary btn-sm"> <i class="fa fa-paper-plane" aria-hidden="true"></i> Reply</button>
                      </div>
                    </form>
                  </div>
                </article>
              @endif
              <div class="replies">
                <div class="reply-list" v-for="reply in replies" v-bind:id="reply.id" >
                  <div v-if="reply.comment_id == comment.id">
                    <article class="row">
                      <div class="col-md-2 col-sm-2 col-md-offset-1 col-sm-offset-0 hidden-xs">
                        <figure class="thumbnail">
                          <img class="img-responsive" v-bind:src="getAvatar(reply.avatar)">
                        </figure>
                      </div>
                      <div class="col-md-9 col-sm-9">
                        <div class="panel panel-default arrow left">
                          <div class="panel-heading right">Reply
                            @if (Auth::user())
                              <div class="btn-group pull-right" v-if="reply.user_id == {{ Auth::user()->id }}">
                                <button data-toggle="dropdown" class="btn btn-link dropdown-toggle" type="button"><span class="fui-triangle-down-small"></span></button>
                                <ul role="menu" class="dropdown-menu">
                                  <li><a href="#" @click.prevent="deleteReply(reply.id, reply)">Delete</a></li>
                                </ul>
                              </div>
                            @endif
                          </div>
                          <div class="panel-body">
                            <header class="text-left">
                              <div class="comment-user"><span class="fui-user"></span>&nbsp;&nbsp;
                              <a href="#" @click.prevent="showUsersInfo(reply.user_id)"> 
                                @{{ reply.firstname }} @{{ reply.lastname }}
                              </a>
                              <div>
                              <time class="comment-date like-this no-padding" datetime="16-12-2014 01:05"><i class="fa fa-clock-o"></i>&nbsp;&nbsp; @{{getTime(reply.created_at)}}</time>
                            </header>
                            <div class="comment-post">
                              <p>
                                @{{ reply.reply }}
                              </p>
                            </div>
                            @if (Auth::user())
                              <div class="text-right">
                                <a href="#" class="btn btn-default btn-xs"><i class="fa fa-reply"></i> reply</a>
                              </div>
                            @endif
                          </div>
                        </div>
                      </div>
                    </article>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-if="comments.length == 0" class="center">
            <p>No comments yet</p>
          </div>
        </div> 
      </div>
    </div>
  </div>
</div>
<div class="modal right fade" id="show-profile" tabindex="-1" role="dialog" aria-labelledby="myModalLabel2">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div v-if="show_profile">
        <div class="modal-header bg-white">
          <button type="button" @click="closeProfile" class="btn btn-success btn-sm pull-right show-profile-close-btn" data-dismiss="modal" aria-label="Close">Close</button>
          <p class="lead no-bottom-margin" id="myModalLabel2">
            @{{show_user_info.firstname}} @{{show_user_info.lastname}}'s Profile
          </p>
        </div>
        <div class="modal-body">
          <div class="row modal-profile">
            <div class="col-md-8 col-md-offset-2">
              <img class="img-responsive round-avatar" v-bind:src="getAvatar(show_user_info.avatar)">
            </div>
            <div class="form-padding">
              <table class="table">
                <tr>
                  <td>Member since:</td>
                  <td>@{{ show_user_info.created_at }}</td>
                </tr>
                <tr>
                  <td>Bio:</td>
                  <td>@{{ show_user_info.bio }}</td>
                </tr>
                <tr>
                  <td>Email:</td>
                  <td>@{{ show_user_info.email }}</td>
                </tr>
              </table>
              <p class="bg-white activities-header">Activity</p>
              <div v-for="activity in activities" class="activities">
                <a v-bind:href="showPost(activity.post_id, activity.id)">
                  <div class="row">
                    <div class="col-md-8">
                      <small>
                        &nbsp;&nbsp;&nbsp;<span class="fui-chat"></span>&nbsp;&nbsp;&nbsp;&nbsp; 
                        @{{ activity.title }}
                      </small>
                    </div>
                    <div class="col-md-4 text-right">
                      <small>@{{ getTime(activity.created_at) }}</small>
                    </div>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="full-height flex-center">
        <h6 class="center"> <i class="fa fa-circle-o-notch fa-spin" aria-hidden="true"></i> Loading Profile...</h6>
      </div>
    </div>
  </div>
</div>
@endsection