
first todo = properly define todo's in milestones

3.1.1
- release early version as pre-release asap
- add connection prop to model !
- add guarded prop to model ! (only empty / AI-fk)



3.1.2
- implement config setting: excludes tables to proces 
    (migrations, jobs, job_batches, cache, cache_locks, failed_jobs, password_reset_tokens, sessions, etc)
- implement config setting: option to not overwrite model if it already exists
- implement config setting: option to not overwrite traits if it already exists
- cleanup methods (return types etc)

- casts (json, timestamps) - use auto-cast=false

- timestamps
- softdeletes


3.1.3

- detect and thus require the config file to be published in the app/config directory

3.2.0

- morph relationships (...type + ...id)
- 3rd-party relationships (staudenmeir)
  ------> theorie: zal mogelijk conflicting relation names geven dus moet de laatste zijn om te implementeren
  ------> https://stackoverflow.com/questions/21699050/many-to-many-relationships-in-laravel-belongstomany-vs-hasmanythrough

     eloquent-has-many-deep (perhaps excluding pivots, will need clear mysqlworkbench example)
     laravel-adjacency-list
     belongs-to-through (hoeveel levels diep ? => config) 
     (eloquent-json-relations) <-- likely unfeasable


- [needs example] consider relations less verbose if automatically detectable via column names


- HasManyRelations etc ~> ctor arguments are nullable for now but shouldnt be because relations generator expects them present
