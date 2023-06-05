- timestamps
- softdeletes
- specify database
- detect hasManyThrough etc  (see other generator packages for that ?)
- morph (...type + ...id)
- belongsToThrough (hoeveel levels diep ?) ~> alles !
- hasManyThrough (hoeveel levels diep ?) 1,2, 3, 4 of 5 excluding pivots
    => theorie: zal mogelijk conflicting relation names geven dus moet de laatste zijn om te implementeren
    => https://stackoverflow.com/questions/21699050/many-to-many-relationships-in-laravel-belongstomany-vs-hasmanythrough
- config automatically excludes tables to proces (migrations, etc)
- consider relations less verbose if automatically detectable via column names


- HasManyRelations etc ~> ctor arguments are nullable for now but shouldnt be because relations generator expects them present