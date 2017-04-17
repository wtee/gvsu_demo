<!doctype html>
<html lang="en-US">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Research Start</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/3.0.3/normalize.min.css" />
    <link rel="stylesheet" href="css/yggdrasil.css" />
    <style>
      .grid {width:90%;}
      * {
        font-family:Georgia, serif;
      }

      body {
        background-color:#fdf6e3;
      }

      h1,
      h2,
      h3,
      h4,
      p {
        color:#002b36;
      }

      .subhead {
        font-size:1.1em;
      }

      label,
      input,
      select {
        display:block;
      }

      input,
      select {
        margin-bottom:2em;
      }
    </style>
  </head>
  <body>
    <?php
    function print_subject_resources($code, $info) {
      echo "<article class=\"grid\">
              <header class=\"c12\">
                <h2><a href=\"{$info->SubjectGuide}\">{$code}: {$info->Subject}</a></h2>
              </header>
              <section class=\"c2\">
                <h3>Liason librarian</h3>
                <img src=\"{$info->Librarian->Photo}\" alt=\"\" height=\"100\" />
                <h4>{$info->Librarian->Name}</h4>
                <p><a href=\"mailto:{$info->Librarian->Email}\">{$info->Librarian->Email}</a><br />
                <a href=\"tel:+1" . preg_replace('/[^0-9]/', '', $info->Librarian->Phone). "\">{$info->Librarian->Phone}</a></p>
              </section>
              <section class=\"c10\">
                <h3>Databases</h3>
                <h4><a href=\"{$info->Database1->URL}\">{$info->Database1->Name}</a></h4>
                <p>{$info->Database1->Description}</p>
                <h4><a href=\"{$info->Database2->URL}\">{$info->Database2->Name}</a></h4>
                <p>{$info->Database2->Description}</p>
                <h4><a href=\"{$info->Database3->URL}\">{$info->Database3->Name}</a></h4>
                <p>{$info->Database3->Description}</p>
              </section>
            </article>";
    }

    $time_file = 'time.txt';
    $json_file = 'researchstart.json';
    $time_limit = 300; // Limit new JSON request to 5 minute intervals

    // Check if this page has run before
    if (file_exists($time_file)) {
      $last_request = file_get_contents($time_file);

      // If the last request was made since the caching period defined
      // by $time_limit expired, request a new JSON file.
      if ($_SERVER['REQUEST_TIME'] - $last_request > $time_limit) {
        $research_json_string = file_get_contents('https://prod.library.gvsu.edu/labs/researchstart/researchstart.json');
        // If the request failed, continue using cached file.
        if ($research_json_string == false) {
          $research_json_string = file_get_contents('researchstart.json');
        // Otherwise, write new cached file and log request time.
        } else {
          file_put_contents($json_file, $research_json_string);
          file_put_contents($time_file, $_SERVER['REQUEST_TIME']);
        }
      } else {
        $research_json_string = file_get_contents($json_file);
      }
    // If this is the first time this page has run, fetch JSON, create cached JSON file
    // and log request time.
    } else {
      $research_json_string = file_get_contents('https://prod.library.gvsu.edu/labs/researchstart/researchstart.json');
      file_put_contents($json_file, $research_json_string);
      file_put_contents($time_file, $_SERVER['REQUEST_TIME']);
    }

    $research_json = json_decode($research_json_string);

    ?>
    <main class="grid">
      <header class="c12">
        <h1>Research Start</h1>
        <h2 class="subhead">Need help researching a topic? Search, select, or browse GVSU's research guides, databases, and helpful librarians.</h2>
      </header>
      <form action="index.php" method="post" class="c12">
        <label for="research-search">Search for a research guide: </label>
        <input id="research-search" type="text" name="research_search" placeholder="Search by course code or name..." />
        <label for="research-options">Or choose one from this list: </label>
        <select id="research-options" name="research_options">
          <option value="">Select a subject</option>
          <?php
            // Build select menu.
            foreach($research_json as $code => $v) {
              echo "<option value=\"{$code}\">{$code}: {$v->Subject}</option>";
            }
          ?>
        </select>
        <input id="research-search-submit" type="submit" value="Find A Guide" />
      </form>
      <?php
        $results = array();

        foreach($research_json as $k  => $v) {
          if (isset($_POST['research_search'])) {
            $query = preg_split('/[ -]/', strtolower(trim($_POST['research_search'])));

            if ($query[0] != '') {
              // Check if $query matches a subject code like ACC or CJ or if it
              // matches all or part of a subject name like Hospitality and Toursim Management.
              if ($query[0] == strtolower($k) || preg_match('/.*'.$query[0].'.*/i', $v->Subject)) {
                $results[] = array($k, $v);
                // If the last item is numeric, the search is probably for a specific class,
                // so there's no need to return all possible matches.
                if (is_numeric(end($query))) {
                  break;
                }
              }
            // If no search terms were entered, check to see if the select menu was used.
            } elseif ($_POST['research_options'] != '') {
              if ($_POST['research_options'] == $k) {
                $results[] = array($k, $v);
              }
            // If both search fields are empty, return all posible results.
            } else {
              $results[] = array($k, $v);
            }
          // If this is the initial page load, return all possible results.
          } else {
            $results[] = array($k, $v);
          }
        }

        // Remove duplicate results from searches like "COM", which will match
        // both subject code COM and subject name Communication Studies.
        $output = array_unique($results, $sort_flags = SORT_REGULAR);

        foreach($output as $a) {
          print_subject_resources($a[0], $a[1]);
        }
      ?>
    </main>
    <div id="cms-footer-wrapper">
    </div>
    <div id="cms-copyright-wrapper">
    </div>
  </body>
</html>
