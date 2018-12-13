<h1 align="center"> laravel-query-logger </h1>

<p align="center"> Log Laravel application request & command runtime queries..</p>

## Installing

```shell
$ composer require mugennsou/laravel-query-logger --dev -vvv
```

## Usage

```bash
tail storage/logs/laravel.log -f
```

#### request log

```
[2018-12-13 10:33:12] local.DEBUG: 
============ GET : http://dazhou.localhost/api/regions/2?filter[depth]=1 ============

ACTION: Mugennsou\LaravelChinaRegion\Http\Controllers\RegionController@show
SQL COUNT: 2
SQL RUNTIME: 2.29 ms

[1.73 ms] select * from `regions` where `regions`.`id` = 2 limit 1
[0.56 ms] select * from `regions` where `regions`.`parent_id` in (2)
```

#### command log

```
[2018-12-13 10:36:20] local.DEBUG: 
============ migrate ============

EXIT CODE: 0
SQL COUNT: 2
SQL RUNTIME: 13.07 ms

[12.13 ms] select * from information_schema.tables where table_schema = dazhou and table_name = migrations
[0.94 ms] select `migration` from `migrations` order by `batch` asc, `migration` asc
```

## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/mugennsou/laravel-query-logger/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/mugennsou/laravel-query-logger/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT
