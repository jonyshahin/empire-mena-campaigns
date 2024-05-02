@servers(['web' => 'cg9wtvwaz42e@92.205.7.84'])

@setup
    $repository = 'git@gitlab.com:jony.shahin/empire-mena-campaign.git';
    $releases_dir = '/home/cg9wtvwaz42e/public_html/api-empire-mena-campaign.beinwebsite.com/releases';
    $app_dir = '/home/cg9wtvwaz42e/public_html/api-empire-mena-campaign.beinwebsite.com';
    $release = date('YmdHis');
    $new_release_dir = $releases_dir .'/'. $release;
@endsetup

@story('deploy')
    clone_repository
    run_composer
    update_symlinks
@endstory

@task('clone_repository')
    echo 'Remove Old Release Directory'
    rm -rf {{ $releases_dir }}
    echo 'Cloning repository'
    [ -d {{ $releases_dir }} ] || mkdir {{ $releases_dir }}
    git clone --depth 1 {{ $repository }} {{ $new_release_dir }}
    cd {{ $new_release_dir }}
    git reset --hard {{ $commit }}
@endtask

@task('run_composer')
    echo "Starting deployment ({{ $release }})"
    cd {{ $new_release_dir }}
    composer install --prefer-dist --no-scripts -q -o
    php artisan cache:clear
    php artisan view:clear
    php artisan route:clear
    php artisan event:clear
    php artisan config:clear
@endtask

@task('update_symlinks')
    echo "Linking storage directory"
    rm -rf {{ $new_release_dir }}/storage
    ln -nfs {{ $app_dir }}/storage {{ $new_release_dir }}/storage

    echo 'Linking .env file'
    ln -nfs {{ $app_dir }}/.env {{ $new_release_dir }}/.env

    echo 'Linking current release'
    ln -nfs {{ $new_release_dir }} {{ $app_dir }}/current
@endtask
