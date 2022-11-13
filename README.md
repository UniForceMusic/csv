# csv
A small CSV parsing library for PHP

# Include Csv in your project
Include the following include in your file:
```
use Uniforcemusic\Csv\Csv;
```

There are 3 ways to parse a CSV file/string (delimiter is optional):
```
$csv = Csv::parseFromFile($filepath, $delimiter);

$csv = Csv::parseFromString($string, $delimiter);

$csv = new Csv($string, $delimiter);
```

Retrieve data from the Csv module by using either of the following methods:
```
getKeys();

getRows();

getString();

writeToFile($filepath);
```

To manipulate the data in the Csv you can use the data manipulation methods
```
$csv->filter($callback);

$csv->map($callback);

$csv->add([
    "key" => "value"
])
```