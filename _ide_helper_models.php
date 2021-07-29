<?php

// @formatter:off
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * App\Models\Link
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $user_id
 * @property string $url
 * @property int|null $is_short
 * @property string $title
 * @property string $description
 * @property string|null $slack_url
 * @property string $slack_ts
 * @property-read mixed $liked
 * @property-read mixed $unread
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\UserLike[] $likes
 * @property-read int|null $likes_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\LinkReadStatus[] $read_statuses
 * @property-read int|null $read_statuses_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $tags
 * @property-read int|null $tags_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Link newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Link newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Link query()
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereIsShort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereSlackTs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereSlackUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereUserId($value)
 */
	class Link extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\LinkReadStatus
 *
 * @property int $id
 * @property int $link_id
 * @property int $user_id
 * @property-read \App\Models\Link $link
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|LinkReadStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LinkReadStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LinkReadStatus query()
 * @method static \Illuminate\Database\Eloquent\Builder|LinkReadStatus whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkReadStatus whereLinkId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkReadStatus whereUserId($value)
 */
	class LinkReadStatus extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Tag
 *
 * @property int $id
 * @property string $name
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Link[] $links
 * @property-read int|null $links_count
 * @method static \Illuminate\Database\Eloquent\Builder|Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Tag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Tag query()
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereName($value)
 */
	class Tag extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $slack_id
 * @property string|null $slack_token
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Sanctum\PersonalAccessToken[] $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereSlackId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereSlackToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\UserLike
 *
 * @property int $id
 * @property int $link_id
 * @property int $user_id
 * @property-read \App\Models\Link $link
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|UserLike newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserLike newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserLike query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserLike whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserLike whereLinkId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserLike whereUserId($value)
 */
	class UserLike extends \Eloquent {}
}

