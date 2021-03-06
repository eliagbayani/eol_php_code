<?php
namespace php_active_record;
class WikiHTMLAPI
{
    function __construct()
    {
    }
    private function get_tag_name($html) //set to public during development
    {
        $arr = explode(" ", $html);
        $tag = $arr[0];
        return $tag; //e.g. "<div" or "<table"
    }
    public function get_real_coverage($left, $html)
    {
        $final = array();
        while($html = self::main_real_coverage($left, $html)) {
            if($html) $final[] = $html;
        }
        if($final) return end($final);
        return false;
    }
    public function process_needle($html, $needle, $multipleYN = false) //not multiple process, but only one search of a needle
    {
        $orig = $html;
        if($tmp = $this->get_pre_tag_entry($html, $needle)) {
            $left = $tmp . $needle;
            $html = self::process_left($html, $left);

            if($multipleYN) { //a little dangerous, if not used properly it will nuke the html
                // /* should work but it nukes html
                if($orig == $html) return $html;
                else $html = self::process_needle($html, $needle, true);
                // */
            }
            else return $html; //means single process only
        }
        return $html;
    }
    public function process_left($html, $left)
    {
        if($val = self::get_real_coverage($left, $html)) $html = $val; //get_real_coverage() assumes that html has balanced open and close tags.
        return $html;
    }
    private function main_real_coverage($left, $html)
    {
        //1st: get tag name 
        $tag = self::get_tag_name($left);
        if(!$tag) return false;
        // echo "\ntag_name: [$tag]\n";
        
        //2nd get pos of $left
        $pos = strpos($html, $left);
        if($pos === false) return false;
        // echo "\npos of left: [$pos]\n";
        // echo "\n".substr($html, $pos, 10)."\n"; //debug
        
        //3rd: get ending tag of $tag
        $ending_tag = self::get_ending_tag($tag); // e.g. </table>
        // echo "\nending_tag: [$ending_tag]\n";
        
        //4th: initialize vars
        $open_tag = 1;
        $close_tag = 0;
        $len = strlen($tag);
        
        //5th: start moving substr in search of open_tag (<table) and close_tag (</table>)
        $start_pos = $pos+1;
        while($open_tag >= 1) {
            $str = substr($html, $start_pos, $len);
            // echo "\nopen: [$str]";
            if($str == $tag) $open_tag++;
            
            $str2 = substr($html, $start_pos, $len+2);
            // echo "\nclose: [$str2]";
            if($str2 == "") return false; //meaning the html does not have a balance open and close tags. Invalid html structure.
            if($str2 == $ending_tag) $open_tag--;
            
            if($open_tag < 1) break;
            $start_pos++;
        }
        // echo "\nfinal open: [$str]";
        // echo "\nfinal close: [$str2]";
        
        //6th get substr of entire coverage
        $num = $start_pos + ($len+2);
        $num = $num - $pos;
        $final = substr($html, $pos, $num);
        return str_replace($final, '', $html);
    }
    private function get_ending_tag($tag)
    {   //e.g. "<table"
        return str_replace("<", "</", $tag). ">";
    }
    public function process_external_links($html, $id)
    {
        $left = '<span class="mw-headline" id="'.$id.'"'; $right = '<span class="mw-headline"';
        $html1 = $this->remove_all_in_between_inclusive($left, $right, $html, false);
        
        $left = '<span class="mw-headline" id="'.$id.'"'; $right = '<!--';
        $html2 = $this->remove_all_in_between_inclusive($left, $right, $html, false);
        
        if($html1 && $html2) {
            if($html1 != $html && $html2 != $html) {
                if(strlen($html1) < strlen($html2)) return $html2;  //exit("\naaa\n"); //
                else return $html1;                                 //exit("\nccc\n");
            }
            elseif($html1 != $html && $html2 == $html) return $html1;
            elseif($html1 == $html && $html2 != $html) return $html2;
            else return $html; //no change
        }
        /* does not go here...
        elseif($html1) return $html1;   //exit("\nbbb\n"); //
        elseif($html2) return $html2;   //exit("\nccc\n");//
        else return $html;              //exit("\nddd\n"); //
        */
        return $html; //shouldn't go here, actually...
    }
    public function during_dev()
    {   $arr = false;
        if($this->debug_taxon == 'Formicidae')      {$arr = $this->get_object('Q7386'); $arr = $arr->entities->Q7386;}
        if($this->debug_taxon == "Gadus morhua")    {$arr = $this->get_object('Q199788'); $arr = $arr->entities->Q199788;}
        if($this->debug_taxon == "fish Pisces")     {$arr = $this->get_object('Q152'); $arr = $arr->entities->Q152;}
        if($this->debug_taxon == "starfish Asteroidea")     {$arr = $this->get_object('Q25349'); $arr = $arr->entities->Q25349;}
        if($this->debug_taxon == "Orca")                    {$arr = $this->get_object('Q26843'); $arr = $arr->entities->Q26843;}
        if($this->debug_taxon == "Shark Selachimorpha")     {$arr = $this->get_object('Q7372'); $arr = $arr->entities->Q7372;}
        if($this->debug_taxon == "Pacific halibut")         {$arr = $this->get_object('Q1819782'); $arr = $arr->entities->Q1819782;}
        if($this->debug_taxon == "Pale fox")                {$arr = $this->get_object('Q739525'); $arr = $arr->entities->Q739525;}
        if($this->debug_taxon == "Chanos chanos")           {$arr = $this->get_object('Q465261'); $arr = $arr->entities->Q465261;}
        if($this->debug_taxon == "Oreochromis niloticus")   {$arr = $this->get_object('Q311170'); $arr = $arr->entities->Q311170;}
        if($this->debug_taxon == "Polar bear")       {$arr = $this->get_object('Q33609'); $arr = $arr->entities->Q33609;}
        if($this->debug_taxon == "Angiosperms")      {$arr = $this->get_object('Q25314'); $arr = $arr->entities->Q25314;} //DATA-1803
        if($this->debug_taxon == "Mus musculus")     {$arr = $this->get_object('Q83310'); $arr = $arr->entities->Q83310;}
        if($this->debug_taxon == "Rodentia")         {$arr = $this->get_object('Q10850'); $arr = $arr->entities->Q10850;}
        if($this->debug_taxon == "Animalia")         {$arr = $this->get_object('Q729'); $arr = $arr->entities->Q729;}
        if($this->debug_taxon == "Plantae")          {$arr = $this->get_object('Q756'); $arr = $arr->entities->Q756;}
        if($this->debug_taxon == "Virus")            {$arr = $this->get_object('Q808'); $arr = $arr->entities->Q808;}
        if($this->debug_taxon == "ferns")            {$arr = $this->get_object('Q80005'); $arr = $arr->entities->Q80005;}
        if($this->debug_taxon == "Acacia")           {$arr = $this->get_object('Q81666'); $arr = $arr->entities->Q81666;}
        if($this->debug_taxon == "Panthera tigris")  {$arr = $this->get_object('Q19939'); $arr = $arr->entities->Q19939;}
        if($this->debug_taxon == "Bald Eagle")       {$arr = $this->get_object('Q127216'); $arr = $arr->entities->Q127216;}
        if($this->debug_taxon == "Aves")             {$arr = $this->get_object('Q5113'); $arr = $arr->entities->Q5113;}
        if($this->debug_taxon == "sunflower")        {$arr = $this->get_object('Q171497'); $arr = $arr->entities->Q171497;}
        if($this->debug_taxon == "Rosa")             {$arr = $this->get_object('Q34687'); $arr = $arr->entities->Q34687;} //genus of plant
        if($this->debug_taxon == "Hominidae")        {$arr = $this->get_object('Q635162'); $arr = $arr->entities->Q635162;}  //-- Homo sapiens
        if($this->debug_taxon == "Fungi")            {$arr = $this->get_object('Q764'); $arr = $arr->entities->Q764;}
        if($this->debug_taxon == "Coronaviridae")    {$arr = $this->get_object('Q1134583'); $arr = $arr->entities->Q1134583;}
        if($this->debug_taxon == "Tracheophyta")     {$arr = $this->get_object('Q27133'); $arr = $arr->entities->Q27133;}
        if($this->debug_taxon == "Leuciscus cephalus") {$arr = $this->get_object('Q189014'); $arr = $arr->entities->Q189014;}
        //lang ce
        if($this->debug_taxon == "Solanum tuberosum")       {$arr = $this->get_object('Q10998'); $arr = $arr->entities->Q10998;}
        if($this->debug_taxon == "Gruidae")                 {$arr = $this->get_object('Q25365'); $arr = $arr->entities->Q25365;}
        if($this->debug_taxon == "Cyanistes caeruleus")     {$arr = $this->get_object('Q25404'); $arr = $arr->entities->Q25404;}
        if($this->debug_taxon == "Haliaeetus albicilla")    {$arr = $this->get_object('Q25438'); $arr = $arr->entities->Q25438;}
        if($this->debug_taxon == "Lynx lynx")               {$arr = $this->get_object('Q43375'); $arr = $arr->entities->Q43375;}
        if($this->debug_taxon == "Ferula assa-foetida")     {$arr = $this->get_object('Q111185'); $arr = $arr->entities->Q111185;}
        if($this->debug_taxon == "Milvus milvus")           {$arr = $this->get_object('Q156250'); $arr = $arr->entities->Q156250;}
        if($this->debug_taxon == "Gratiola")                {$arr = $this->get_object('Q159388'); $arr = $arr->entities->Q159388;}
        if($this->debug_taxon == "Circaetus gallicus")      {$arr = $this->get_object('Q170251'); $arr = $arr->entities->Q170251;}
        if($this->debug_taxon == "Gyps fulvus")             {$arr = $this->get_object('Q177856'); $arr = $arr->entities->Q177856;}
        //lang yo
        if($this->debug_taxon == "Microlophus habelii")         {$arr = $this->get_object('Q3020789'); $arr = $arr->entities->Q3020789;}
        if($this->debug_taxon == "Hesperentomidae")             {$arr = $this->get_object('Q3040549'); $arr = $arr->entities->Q3040549;}
        if($this->debug_taxon == "Paranisentomon")              {$arr = $this->get_object('Q4495001'); $arr = $arr->entities->Q4495001;}
        if($this->debug_taxon == "Silvestridia")                {$arr = $this->get_object('Q4548129'); $arr = $arr->entities->Q4548129;}
        if($this->debug_taxon == "Crocodylus niloticus")        {$arr = $this->get_object('Q168745'); $arr = $arr->entities->Q168745;}
        if($this->debug_taxon == "Necrosyrtes monachus")        {$arr = $this->get_object('Q177386'); $arr = $arr->entities->Q177386;}
        if($this->debug_taxon == "Pandion haliaetus")           {$arr = $this->get_object('Q25332'); $arr = $arr->entities->Q25332;}
        if($this->debug_taxon == "Manihot esculenta")           {$arr = $this->get_object('Q83124'); $arr = $arr->entities->Q83124;}
        if($this->debug_taxon == "Crocuta crocuta")             {$arr = $this->get_object('Q178089'); $arr = $arr->entities->Q178089;}
        if($this->debug_taxon == "Microlophus theresioides")    {$arr = $this->get_object('Q3006767'); $arr = $arr->entities->Q3006767;}
        //lang kv
        if($this->debug_taxon == "Rhinocerotidae")              {$arr = $this->get_object('Q34718'); $arr = $arr->entities->Q34718;}
        if($this->debug_taxon == "Sciuridae")                   {$arr = $this->get_object('Q9482'); $arr = $arr->entities->Q9482;}
        if($this->debug_taxon == "Vaccinium subg. Oxycoccus")   {$arr = $this->get_object('Q13181'); $arr = $arr->entities->Q13181;}
        if($this->debug_taxon == "Litchi chinensis")            {$arr = $this->get_object('Q13182'); $arr = $arr->entities->Q13182;}
        //lang vls
        if($this->debug_taxon == "Actinopterygii")              {$arr = $this->get_object('Q127282'); $arr = $arr->entities->Q127282;}
        if($this->debug_taxon == "Esox lucius")                 {$arr = $this->get_object('Q165278'); $arr = $arr->entities->Q165278;}
        //lang co
        if($this->debug_taxon == "Gallinula chloropus")         {$arr = $this->get_object('Q18847'); $arr = $arr->entities->Q18847;}
        if($this->debug_taxon == "Cornales")                    {$arr = $this->get_object('Q21769'); $arr = $arr->entities->Q21769;}
        if($this->debug_taxon == "Proteales")                   {$arr = $this->get_object('Q21838'); $arr = $arr->entities->Q21838;}
        if($this->debug_taxon == "Felidae")                     {$arr = $this->get_object('Q25265'); $arr = $arr->entities->Q25265;}
        if($this->debug_taxon == "Tyto alba")                   {$arr = $this->get_object('Q25317'); $arr = $arr->entities->Q25317;}
        if($this->debug_taxon == "Prunus dulcis")               {$arr = $this->get_object('Q39918'); $arr = $arr->entities->Q39918;}
        if($this->debug_taxon == "Gagea minima")                {$arr = $this->get_object('Q159444'); $arr = $arr->entities->Q159444;}
        if($this->debug_taxon == "Clematis vitalba")            {$arr = $this->get_object('Q160100'); $arr = $arr->entities->Q160100;}
        if($this->debug_taxon == "Hyoscyamus niger")            {$arr = $this->get_object('Q161058'); $arr = $arr->entities->Q161058;}
        //lang mi
        if($this->debug_taxon == "Hydroprogne caspia")          {$arr = $this->get_object('Q27129'); $arr = $arr->entities->Q27129;}
        if($this->debug_taxon == "Fabaceae")                    {$arr = $this->get_object('Q44448'); $arr = $arr->entities->Q44448;}
        if($this->debug_taxon == "Xenicus gilviventris")        {$arr = $this->get_object('Q135589'); $arr = $arr->entities->Q135589;}
        if($this->debug_taxon == "Knightia excelsa")            {$arr = $this->get_object('Q311623'); $arr = $arr->entities->Q311623;}
        if($this->debug_taxon == "Metrosideros excelsa")        {$arr = $this->get_object('Q311747'); $arr = $arr->entities->Q311747;}
        if($this->debug_taxon == "Pterodroma macroptera")       {$arr = $this->get_object('Q313391'); $arr = $arr->entities->Q313391;}
        if($this->debug_taxon == "Dacrydium cupressinum")       {$arr = $this->get_object('Q382469'); $arr = $arr->entities->Q382469;}
        if($this->debug_taxon == "Himantopus novaezelandiae")   {$arr = $this->get_object('Q686269'); $arr = $arr->entities->Q686269;}
        if($this->debug_taxon == "Callaeas cinereus")           {$arr = $this->get_object('Q760949'); $arr = $arr->entities->Q760949;}
        if($this->debug_taxon == "Agathis australis")           {$arr = $this->get_object('Q955413'); $arr = $arr->entities->Q955413;}
        if($this->debug_taxon == "Calamus baratangensis")       {$arr = $this->get_object('Q15458835'); $arr = $arr->entities->Q15458835;}
        //lang mdf
        if($this->debug_taxon == "Adenoncos")               {$arr = $this->get_object('Q20818'); $arr = $arr->entities->Q20818;}
        if($this->debug_taxon == "Ancistrochilus")          {$arr = $this->get_object('Q20957'); $arr = $arr->entities->Q20957;}
        if($this->debug_taxon == "Acanthephippium")         {$arr = $this->get_object('Q21118'); $arr = $arr->entities->Q21118;}
        if($this->debug_taxon == "Cucumis sativus")         {$arr = $this->get_object('Q23425'); $arr = $arr->entities->Q23425;}
        if($this->debug_taxon == "Solanum lycopersicum")    {$arr = $this->get_object('Q23501'); $arr = $arr->entities->Q23501;}
        if($this->debug_taxon == "Brassavola")              {$arr = $this->get_object('Q94815'); $arr = $arr->entities->Q94815;}
        if($this->debug_taxon == "Convolvulus arvensis")    {$arr = $this->get_object('Q111346'); $arr = $arr->entities->Q111346;}
        //lang to
        if($this->debug_taxon == "Orchidaceae")             {$arr = $this->get_object('Q25308'); $arr = $arr->entities->Q25308;}
        if($this->debug_taxon == "Onychoprion anaethetus")  {$arr = $this->get_object('Q28490'); $arr = $arr->entities->Q28490;}
        if($this->debug_taxon == "Senna alata")             {$arr = $this->get_object('Q41504'); $arr = $arr->entities->Q41504;}
        if($this->debug_taxon == "Curcuma longa")           {$arr = $this->get_object('Q42562'); $arr = $arr->entities->Q42562;}
        if($this->debug_taxon == "Anisoptera")              {$arr = $this->get_object('Q80066'); $arr = $arr->entities->Q80066;}
        if($this->debug_taxon == "Solanum")                 {$arr = $this->get_object('Q146555'); $arr = $arr->entities->Q146555;}
        if($this->debug_taxon == "Euphorbia")               {$arr = $this->get_object('Q146567'); $arr = $arr->entities->Q146567;}
        //lang kbd
        if($this->debug_taxon == "Aegolius funereus")     {$arr = $this->get_object('Q174466'); $arr = $arr->entities->Q174466;}
        if($this->debug_taxon == "Plegadis falcinellus")  {$arr = $this->get_object('Q178811'); $arr = $arr->entities->Q178811;}
        if($this->debug_taxon == "Turdus viscivorus")     {$arr = $this->get_object('Q178942'); $arr = $arr->entities->Q178942;}
        if($this->debug_taxon == "Meropidae")             {$arr = $this->get_object('Q183147'); $arr = $arr->entities->Q183147;}
        if($this->debug_taxon == "Anser")                 {$arr = $this->get_object('Q183361'); $arr = $arr->entities->Q183361;}
        if($this->debug_taxon == "Prunus")                {$arr = $this->get_object('Q190545'); $arr = $arr->entities->Q190545;}
        if($this->debug_taxon == "Cairina moschata")      {$arr = $this->get_object('Q242851'); $arr = $arr->entities->Q242851;}
        //lang tg
        if($this->debug_taxon == "Jasminum")              {$arr = $this->get_object('Q82014'); $arr = $arr->entities->Q82014;}
        //lang mt
        if($this->debug_taxon == "Scandentia")              {$arr = $this->get_object('Q231550'); $arr = $arr->entities->Q231550;}
        if($this->debug_taxon == "Peramelemorphia")         {$arr = $this->get_object('Q244587'); $arr = $arr->entities->Q244587;}
        if($this->debug_taxon == "Ochotona nubrica")        {$arr = $this->get_object('Q311579'); $arr = $arr->entities->Q311579;}
        if($this->debug_taxon == "Ochotona nigritia")       {$arr = $this->get_object('Q311599'); $arr = $arr->entities->Q311599;}
        if($this->debug_taxon == "Tupaia belangeri")        {$arr = $this->get_object('Q378959'); $arr = $arr->entities->Q378959;}
        if($this->debug_taxon == "Ameridelphia")            {$arr = $this->get_object('Q384427'); $arr = $arr->entities->Q384427;}
        if($this->debug_taxon == "Lepus castroviejoi")      {$arr = $this->get_object('Q430175'); $arr = $arr->entities->Q430175;}
        if($this->debug_taxon == "Minusculodelphis")        {$arr = $this->get_object('Q539208'); $arr = $arr->entities->Q539208;}
        if($this->debug_taxon == "Euarchontoglires")        {$arr = $this->get_object('Q471797'); $arr = $arr->entities->Q471797;}
        if($this->debug_taxon == "Lepus granatensis")       {$arr = $this->get_object('Q513373'); $arr = $arr->entities->Q513373;}
        if($this->debug_taxon == "Pronolagus rupestris")    {$arr = $this->get_object('Q536459'); $arr = $arr->entities->Q536459;}
        if($this->debug_taxon == "Nesolagus timminsi")      {$arr = $this->get_object('Q564479'); $arr = $arr->entities->Q564479;}
        if($this->debug_taxon == "Antechinus")              {$arr = $this->get_object('Q650656'); $arr = $arr->entities->Q650656;}
        // lang or
        if($this->debug_taxon == "Chiroptera")                  {$arr = $this->get_object('Q28425'); $arr = $arr->entities->Q28425;}
        if($this->debug_taxon == "Octopoda")                    {$arr = $this->get_object('Q40152'); $arr = $arr->entities->Q40152;}
        if($this->debug_taxon == "Piper nigrum")                {$arr = $this->get_object('Q43084'); $arr = $arr->entities->Q43084;}
        if($this->debug_taxon == "Canis latrans")               {$arr = $this->get_object('Q44299'); $arr = $arr->entities->Q44299;}
        if($this->debug_taxon == "Dromaius novaehollandiae")    {$arr = $this->get_object('Q93208'); $arr = $arr->entities->Q93208;}
        if($this->debug_taxon == "Saltopus")                    {$arr = $this->get_object('Q132859'); $arr = $arr->entities->Q132859;}
        if($this->debug_taxon == "Bruhathkayosaurus matleyi")   {$arr = $this->get_object('Q132867'); $arr = $arr->entities->Q132867;}
        if($this->debug_taxon == "Eucalyptus globulus")         {$arr = $this->get_object('Q159528'); $arr = $arr->entities->Q159528;}
        if($this->debug_taxon == "Mentha arvensis")             {$arr = $this->get_object('Q160585'); $arr = $arr->entities->Q160585;}
        if($this->debug_taxon == "Oxalis corniculata")          {$arr = $this->get_object('Q162795'); $arr = $arr->entities->Q162795;}
        if($this->debug_taxon == "Jasminum auriculatum")        {$arr = $this->get_object('Q623492'); $arr = $arr->entities->Q623492;}
        // general lang
        if($this->debug_taxon == "Lepidoptera")        {$arr = $this->get_object('Q28319'); $arr = $arr->entities->Q28319;}
        if($this->debug_taxon == "Mammalia")           {$arr = $this->get_object('Q7377'); $arr = $arr->entities->Q7377;}
        // lang bh
        if($this->debug_taxon == "Psilopogon viridis")  {$arr = $this->get_object('Q27074836'); $arr = $arr->entities->Q27074836;}
        if($this->debug_taxon == "Gracupica contra")    {$arr = $this->get_object('Q27075597'); $arr = $arr->entities->Q27075597;}
        if($this->debug_taxon == "Pyrus")               {$arr = $this->get_object('Q434'); $arr = $arr->entities->Q434;}
        if($this->debug_taxon == "Turdus merula")       {$arr = $this->get_object('Q25234'); $arr = $arr->entities->Q25234;}
        if($this->debug_taxon == "Cygnus olor")         {$arr = $this->get_object('Q25402'); $arr = $arr->entities->Q25402;}
        if($this->debug_taxon == "Aurelia aurita")      {$arr = $this->get_object('Q26864'); $arr = $arr->entities->Q26864;}
        if($this->debug_taxon == "Pittidae")            {$arr = $this->get_object('Q217472'); $arr = $arr->entities->Q217472;}
        if($this->debug_taxon == "Syzygium cumini")     {$arr = $this->get_object('Q232571'); $arr = $arr->entities->Q232571;}
        if($this->debug_taxon == "Porzana parva")       {$arr = $this->get_object('Q270680'); $arr = $arr->entities->Q270680;}
        // lang myv
        if($this->debug_taxon == "Secale cereale")      {$arr = $this->get_object('Q12099'); $arr = $arr->entities->Q12099;}
        if($this->debug_taxon == "Lacertilia")          {$arr = $this->get_object('Q15879'); $arr = $arr->entities->Q15879;}
        if($this->debug_taxon == "Pyrrhula pyrrhula")   {$arr = $this->get_object('Q25382'); $arr = $arr->entities->Q25382;}
        // lang bar
        if($this->debug_taxon == "Cyphotilapia frontosa")   {$arr = $this->get_object('Q285838'); $arr = $arr->entities->Q285838;}
        if($this->debug_taxon == "Lacerta viridis")         {$arr = $this->get_object('Q307047'); $arr = $arr->entities->Q307047;}
        if($this->debug_taxon == "Jacaranda")               {$arr = $this->get_object('Q311105'); $arr = $arr->entities->Q311105;}
        if($this->debug_taxon == "Pseudotropheus")          {$arr = $this->get_object('Q311686'); $arr = $arr->entities->Q311686;}
        if($this->debug_taxon == "Dryocopus pileatus")      {$arr = $this->get_object('Q930712'); $arr = $arr->entities->Q930712;}
        if($this->debug_taxon == "Cupressus ×leylandii")    {$arr = $this->get_object('Q1290970'); $arr = $arr->entities->Q1290970;}
        if($this->debug_taxon == "Vespa crabro")            {$arr = $this->get_object('Q30258'); $arr = $arr->entities->Q30258;}
        if($this->debug_taxon == "Corvus")                  {$arr = $this->get_object('Q43365'); $arr = $arr->entities->Q43365;}
        if($this->debug_taxon == "Prunus spinosa")          {$arr = $this->get_object('Q129018'); $arr = $arr->entities->Q129018;}
        if($this->debug_taxon == "Rupicapra rupicapra")     {$arr = $this->get_object('Q131340'); $arr = $arr->entities->Q131340;}
        //lang nap
        if($this->debug_taxon == "Malvaceae")               {$arr = $this->get_object('Q156551'); $arr = $arr->entities->Q156551;}
        if($this->debug_taxon == "Quercus cerris")          {$arr = $this->get_object('Q157277'); $arr = $arr->entities->Q157277;}
        if($this->debug_taxon == "Turdus iliacus")          {$arr = $this->get_object('Q184825'); $arr = $arr->entities->Q184825;}
        if($this->debug_taxon == "Conger conger")           {$arr = $this->get_object('Q212552'); $arr = $arr->entities->Q212552;}
        if($this->debug_taxon == "Merlangius merlangus")    {$arr = $this->get_object('Q273083'); $arr = $arr->entities->Q273083;}
        if($this->debug_taxon == "Quercus ilex")            {$arr = $this->get_object('Q218155'); $arr = $arr->entities->Q218155;}
        if($this->debug_taxon == "Octopus vulgaris")        {$arr = $this->get_object('Q651361'); $arr = $arr->entities->Q651361;}
        if($this->debug_taxon == "Brassica ruvo")           {$arr = $this->get_object('Q702282'); $arr = $arr->entities->Q702282;}
        if($this->debug_taxon == "Ruta")                    {$arr = $this->get_object('Q165250'); $arr = $arr->entities->Q165250;}
        if($this->debug_taxon == "Lacerta bilineata")       {$arr = $this->get_object('Q739025'); $arr = $arr->entities->Q739025;}
        //lang vo
        if($this->debug_taxon == "Peloneustes")             {$arr = $this->get_object('Q242046'); $arr = $arr->entities->Q242046;}
        if($this->debug_taxon == "Odontorhynchus")          {$arr = $this->get_object('Q5030514'); $arr = $arr->entities->Q5030514;}
        if($this->debug_taxon == "Carcharodontosaurus")     {$arr = $this->get_object('Q14431'); $arr = $arr->entities->Q14431;}
        if($this->debug_taxon == "Sus scrofa domesticus")   {$arr = $this->get_object('Q787'); $arr = $arr->entities->Q787;}
        if($this->debug_taxon == "Vulpes vulpes")           {$arr = $this->get_object('Q8332'); $arr = $arr->entities->Q8332;}
        if($this->debug_taxon == "Bacteria")                {$arr = $this->get_object('Q10876'); $arr = $arr->entities->Q10876;}
        if($this->debug_taxon == "Camelotia")               {$arr = $this->get_object('Q29014894'); $arr = $arr->entities->Q29014894;}
        if($this->debug_taxon == "Saurischia")              {$arr = $this->get_object('Q186334'); $arr = $arr->entities->Q186334;}
        if($this->debug_taxon == "Theropoda")               {$arr = $this->get_object('Q188438'); $arr = $arr->entities->Q188438;}
        if($this->debug_taxon == "Ichthyopterygia")         {$arr = $this->get_object('Q2583869'); $arr = $arr->entities->Q2583869;}
        if($this->debug_taxon == "Sanctacaris uncata")      {$arr = $this->get_object('Q2718204'); $arr = $arr->entities->Q2718204;}
        if($this->debug_taxon == "Zea mays")                {$arr = $this->get_object('Q11575'); $arr = $arr->entities->Q11575;}
        if($this->debug_taxon == "Glycine max")             {$arr = $this->get_object('Q11006'); $arr = $arr->entities->Q11006;}
        if($this->debug_taxon == "Keichosaurus")            {$arr = $this->get_object('Q2375167'); $arr = $arr->entities->Q2375167;}
        if($this->debug_taxon == "Amiskwia")                {$arr = $this->get_object('Q15104428'); $arr = $arr->entities->Q15104428;}
        //lang stq
        if($this->debug_taxon == "Rhinolophus euryale")     {$arr = $this->get_object('Q282863'); $arr = $arr->entities->Q282863;}
        if($this->debug_taxon == "Molossidae")              {$arr = $this->get_object('Q737399'); $arr = $arr->entities->Q737399;}
        if($this->debug_taxon == "Eumops")                  {$arr = $this->get_object('Q371577'); $arr = $arr->entities->Q371577;}
        if($this->debug_taxon == "Microtus pennsylvanicus") {$arr = $this->get_object('Q1765085'); $arr = $arr->entities->Q1765085;}
        if($this->debug_taxon == "Cichorium intybus")       {$arr = $this->get_object('Q2544599'); $arr = $arr->entities->Q2544599;}
        //lang ha
        if($this->debug_taxon == "Allium cepa")             {$arr = $this->get_object('Q23485'); $arr = $arr->entities->Q23485;}
        if($this->debug_taxon == "Crocodilia")              {$arr = $this->get_object('Q25363'); $arr = $arr->entities->Q25363;}
        if($this->debug_taxon == "Columba livia")           {$arr = $this->get_object('Q42326'); $arr = $arr->entities->Q42326;}
        if($this->debug_taxon == "Nycticorax nycticorax")   {$arr = $this->get_object('Q126216'); $arr = $arr->entities->Q126216;}
        if($this->debug_taxon == "Salvadora persica")       {$arr = $this->get_object('Q143525'); $arr = $arr->entities->Q143525;}
        if($this->debug_taxon == "Opuntia ficus-indica")    {$arr = $this->get_object('Q144412'); $arr = $arr->entities->Q144412;}
        if($this->debug_taxon == "Ardeola ralloides")       {$arr = $this->get_object('Q191394'); $arr = $arr->entities->Q191394;}
        if($this->debug_taxon == "Ziziphus mucronata")      {$arr = $this->get_object('Q207081'); $arr = $arr->entities->Q207081;}
        if($this->debug_taxon == "Naja nigricollis")        {$arr = $this->get_object('Q386619'); $arr = $arr->entities->Q386619;}
        if($this->debug_taxon == "Cola acuminata")          {$arr = $this->get_object('Q522881'); $arr = $arr->entities->Q522881;}
        //lang lbe
        if($this->debug_taxon == "Capra cylindricornis")    {$arr = $this->get_object('Q854788'); $arr = $arr->entities->Q854788;}
        if($this->debug_taxon == "Mazama")                  {$arr = $this->get_object('Q911770'); $arr = $arr->entities->Q911770;}
        if($this->debug_taxon == "Annona purpurea")         {$arr = $this->get_object('Q2101601'); $arr = $arr->entities->Q2101601;}
        if($this->debug_taxon == "Juglans")                 {$arr = $this->get_object('Q2453469'); $arr = $arr->entities->Q2453469;}
        if($this->debug_taxon == "Allium sativum")          {$arr = $this->get_object('Q23400'); $arr = $arr->entities->Q23400;}
        if($this->debug_taxon == "Erinaceidae")             {$arr = $this->get_object('Q28257'); $arr = $arr->entities->Q28257;}
        if($this->debug_taxon == "Pongo")                   {$arr = $this->get_object('Q41050'); $arr = $arr->entities->Q41050;}
        if($this->debug_taxon == "Talpidae")                {$arr = $this->get_object('Q104825'); $arr = $arr->entities->Q104825;}
        if($this->debug_taxon == "Athene noctua")           {$arr = $this->get_object('Q129958'); $arr = $arr->entities->Q129958;}
        if($this->debug_taxon == "Rupicapra rupicapra")     {$arr = $this->get_object('Q131340'); $arr = $arr->entities->Q131340;}
        //lang lij
        if($this->debug_taxon == "Anas platyrhynchos")      {$arr = $this->get_object('Q25348'); $arr = $arr->entities->Q25348;}
        if($this->debug_taxon == "Perissodactyla")          {$arr = $this->get_object('Q25374'); $arr = $arr->entities->Q25374;}
        if($this->debug_taxon == "Sus scrofa")              {$arr = $this->get_object('Q58697'); $arr = $arr->entities->Q58697;}
        if($this->debug_taxon == "Citrus")                  {$arr = $this->get_object('Q81513'); $arr = $arr->entities->Q81513;}
        if($this->debug_taxon == "Mespilus germanica")      {$arr = $this->get_object('Q146186'); $arr = $arr->entities->Q146186;}
        if($this->debug_taxon == "Canis")                   {$arr = $this->get_object('Q149892'); $arr = $arr->entities->Q149892;}
        if($this->debug_taxon == "Sarcopterygii")           {$arr = $this->get_object('Q160830'); $arr = $arr->entities->Q160830;}
        if($this->debug_taxon == "Dipnoi")                  {$arr = $this->get_object('Q168422'); $arr = $arr->entities->Q168422;}
        if($this->debug_taxon == "Hemichordata")            {$arr = $this->get_object('Q174301'); $arr = $arr->entities->Q174301;}
        if($this->debug_taxon == "Lemur catta")             {$arr = $this->get_object('Q185385'); $arr = $arr->entities->Q185385;}
        //lang ace
        if($this->debug_taxon == "Anura")                   {$arr = $this->get_object('Q53636'); $arr = $arr->entities->Q53636;}
        if($this->debug_taxon == "Istiophorus")             {$arr = $this->get_object('Q127497'); $arr = $arr->entities->Q127497;}
        if($this->debug_taxon == "Bubulcus ibis")           {$arr = $this->get_object('Q132669'); $arr = $arr->entities->Q132669;}
        if($this->debug_taxon == "Senna alexandrina")       {$arr = $this->get_object('Q132675'); $arr = $arr->entities->Q132675;}
        if($this->debug_taxon == "Typha angustifolia")      {$arr = $this->get_object('Q146572'); $arr = $arr->entities->Q146572;}
        if($this->debug_taxon == "Metroxylon sagu")         {$arr = $this->get_object('Q164088'); $arr = $arr->entities->Q164088;}
        if($this->debug_taxon == "Cananga odorata")         {$arr = $this->get_object('Q220963'); $arr = $arr->entities->Q220963;}
        if($this->debug_taxon == "Geopelia striata")        {$arr = $this->get_object('Q288485'); $arr = $arr->entities->Q288485;}
        if($this->debug_taxon == "Lutjanus vitta")          {$arr = $this->get_object('Q302516'); $arr = $arr->entities->Q302516;}
        if($this->debug_taxon == "Phyllanthus emblica")     {$arr = $this->get_object('Q310050'); $arr = $arr->entities->Q310050;}
        if($this->debug_taxon == "Lantana camara")          {$arr = $this->get_object('Q332469'); $arr = $arr->entities->Q332469;}
        if($this->debug_taxon == "Epinephelus coioides")    {$arr = $this->get_object('Q591397'); $arr = $arr->entities->Q591397;}
        if($this->debug_taxon == "Channa striata")          {$arr = $this->get_object('Q686439'); $arr = $arr->entities->Q686439;}
        if($this->debug_taxon == "Sandoricum koetjape")     {$arr = $this->get_object('Q913452'); $arr = $arr->entities->Q913452;}
        if($this->debug_taxon == "Sesbania grandiflora")    {$arr = $this->get_object('Q947251'); $arr = $arr->entities->Q947251;}
        //lang rw
        if($this->debug_taxon == "Olea europaea")               {$arr = $this->get_object('Q37083'); $arr = $arr->entities->Q37083;}
        if($this->debug_taxon == "Lactuca sativa")              {$arr = $this->get_object('Q83193'); $arr = $arr->entities->Q83193;}
        if($this->debug_taxon == "Camellia sinensis")           {$arr = $this->get_object('Q101815'); $arr = $arr->entities->Q101815;}
        if($this->debug_taxon == "Aepyceros melampus")          {$arr = $this->get_object('Q132576'); $arr = $arr->entities->Q132576;}
        if($this->debug_taxon == "Calendula officinalis")       {$arr = $this->get_object('Q145930'); $arr = $arr->entities->Q145930;}
        if($this->debug_taxon == "Passiflora edulis")           {$arr = $this->get_object('Q156790'); $arr = $arr->entities->Q156790;}
        if($this->debug_taxon == "Phyllanthus niruri")          {$arr = $this->get_object('Q2719836'); $arr = $arr->entities->Q2719836;}
        if($this->debug_taxon == "Pennisetum clandestinum")     {$arr = $this->get_object('Q2720980'); $arr = $arr->entities->Q2720980;}
        if($this->debug_taxon == "Yushania alpina")             {$arr = $this->get_object('Q2750563'); $arr = $arr->entities->Q2750563;}
        if($this->debug_taxon == "Acacia polyacantha")          {$arr = $this->get_object('Q3320522'); $arr = $arr->entities->Q3320522;}
        if($this->debug_taxon == "Bridelia micrantha")          {$arr = $this->get_object('Q3644510'); $arr = $arr->entities->Q3644510;}
        if($this->debug_taxon == "Ficus thonningii")            {$arr = $this->get_object('Q3644520'); $arr = $arr->entities->Q3644520;}
        if($this->debug_taxon == "Euphorbia grantii")           {$arr = $this->get_object('Q4022681'); $arr = $arr->entities->Q4022681;}
        if($this->debug_taxon == "Waltheria indica")            {$arr = $this->get_object('Q7966688'); $arr = $arr->entities->Q7966688;}
        if($this->debug_taxon == "Thymus")                      {$arr = $this->get_object('Q131224'); $arr = $arr->entities->Q131224;}
        //lang vec
        if($this->debug_taxon == "Corvus corone")       {$arr = $this->get_object('Q26198'); $arr = $arr->entities->Q26198;}
        if($this->debug_taxon == "Olea europaea")       {$arr = $this->get_object('Q37083'); $arr = $arr->entities->Q37083;}
        if($this->debug_taxon == "Prunus armeniaca")    {$arr = $this->get_object('Q37453'); $arr = $arr->entities->Q37453;}
        if($this->debug_taxon == "Phaseolus vulgaris")  {$arr = $this->get_object('Q42339'); $arr = $arr->entities->Q42339;}
        if($this->debug_taxon == "Taraxacum officinale"){$arr = $this->get_object('Q131219'); $arr = $arr->entities->Q131219;}
        if($this->debug_taxon == "Helianthus tuberosus"){$arr = $this->get_object('Q146190'); $arr = $arr->entities->Q146190;}
        if($this->debug_taxon == "Ribes rubrum")        {$arr = $this->get_object('Q146661'); $arr = $arr->entities->Q146661;}
        //lang kw
        if($this->debug_taxon == "Salix fragilis")              {$arr = $this->get_object('Q157518'); $arr = $arr->entities->Q157518;}
        if($this->debug_taxon == "Viburnum lantana")            {$arr = $this->get_object('Q158508'); $arr = $arr->entities->Q158508;}
        if($this->debug_taxon == "Quercus petraea")             {$arr = $this->get_object('Q158608'); $arr = $arr->entities->Q158608;}
        if($this->debug_taxon == "Taxus baccata")               {$arr = $this->get_object('Q179729'); $arr = $arr->entities->Q179729;}
        if($this->debug_taxon == "Ammodytidae")                 {$arr = $this->get_object('Q695712'); $arr = $arr->entities->Q695712;}
        if($this->debug_taxon == "Fagus longipetiolata")        {$arr = $this->get_object('Q1005330'); $arr = $arr->entities->Q1005330;}
        if($this->debug_taxon == "Quercus cornelius-mulleri")   {$arr = $this->get_object('Q2710363'); $arr = $arr->entities->Q2710363;}
        if($this->debug_taxon == "Fagus mexicana")              {$arr = $this->get_object('Q3145119'); $arr = $arr->entities->Q3145119;}
        if($this->debug_taxon == "Erithacus rubecula")          {$arr = $this->get_object('Q25334'); $arr = $arr->entities->Q25334;}
        if($this->debug_taxon == "Castor")                      {$arr = $this->get_object('Q47542'); $arr = $arr->entities->Q47542;}
        //lang av
        if($this->debug_taxon == "Ursidae")         {$arr = $this->get_object('Q11788'); $arr = $arr->entities->Q11788;}
        if($this->debug_taxon == "Ciconia ciconia") {$arr = $this->get_object('Q25352'); $arr = $arr->entities->Q25352;}
        if($this->debug_taxon == "Oriolus oriolus") {$arr = $this->get_object('Q25388'); $arr = $arr->entities->Q25388;}
        if($this->debug_taxon == "Lens culinaris")  {$arr = $this->get_object('Q131226'); $arr = $arr->entities->Q131226;}
        if($this->debug_taxon == "Populus alba")    {$arr = $this->get_object('Q146269'); $arr = $arr->entities->Q146269;}
        //lang chy
        if($this->debug_taxon == "Alces alces")         {$arr = $this->get_object('Q35517'); $arr = $arr->entities->Q35517;}
        if($this->debug_taxon == "Ursus arctos")        {$arr = $this->get_object('Q36341'); $arr = $arr->entities->Q36341;}
        if($this->debug_taxon == "Phaseolus vulgaris")  {$arr = $this->get_object('Q42339'); $arr = $arr->entities->Q42339;}
        if($this->debug_taxon == "Meleagris")           {$arr = $this->get_object('Q43794'); $arr = $arr->entities->Q43794;}
        if($this->debug_taxon == "Odocoileus virginianus")  {$arr = $this->get_object('Q215887'); $arr = $arr->entities->Q215887;}
        if($this->debug_taxon == "Cardinalidae")            {$arr = $this->get_object('Q223402'); $arr = $arr->entities->Q223402;}
        if($this->debug_taxon == "Athene cunicularia")      {$arr = $this->get_object('Q467068'); $arr = $arr->entities->Q467068;}
        //lang fj
        if($this->debug_taxon == "Pelomedusa subrufa")      {$arr = $this->get_object('Q913795'); $arr = $arr->entities->Q913795;}
        if($this->debug_taxon == "Tupaia moellendorffi")    {$arr = $this->get_object('Q965015'); $arr = $arr->entities->Q965015;}
        if($this->debug_taxon == "Balaka seemannii")        {$arr = $this->get_object('Q1247945'); $arr = $arr->entities->Q1247945;}
        if($this->debug_taxon == "Pelusios subniger")       {$arr = $this->get_object('Q1266050'); $arr = $arr->entities->Q1266050;}
        if($this->debug_taxon == "Astronidium storckii")    {$arr = $this->get_object('Q1616860'); $arr = $arr->entities->Q1616860;}
        if($this->debug_taxon == "Aglaia saltatorum")       {$arr = $this->get_object('Q48271'); $arr = $arr->entities->Q48271;}
        if($this->debug_taxon == "Pinaceae")            {$arr = $this->get_object('Q101680'); $arr = $arr->entities->Q101680;}
        if($this->debug_taxon == "Tupaia belangeri")    {$arr = $this->get_object('Q378959'); $arr = $arr->entities->Q378959;}
        if($this->debug_taxon == "Pandanus")            {$arr = $this->get_object('Q471914'); $arr = $arr->entities->Q471914;}
        if($this->debug_taxon == "Anguilla marmorata")  {$arr = $this->get_object('Q496154'); $arr = $arr->entities->Q496154;}
        //lang ik
        if($this->debug_taxon == "Rattus norvegicus")           {$arr = $this->get_object('Q184224'); $arr = $arr->entities->Q184224;}
        if($this->debug_taxon == "Cinclus")                     {$arr = $this->get_object('Q192575'); $arr = $arr->entities->Q192575;}
        if($this->debug_taxon == "Phalaropus fulicarius")       {$arr = $this->get_object('Q208335'); $arr = $arr->entities->Q208335;}
        if($this->debug_taxon == "Oncorhynchus tshawytscha")    {$arr = $this->get_object('Q833503'); $arr = $arr->entities->Q833503;}
        if($this->debug_taxon == "Branta bernicla nigricans")   {$arr = $this->get_object('Q1277778'); $arr = $arr->entities->Q1277778;}
        if($this->debug_taxon == "Viburnum edule")              {$arr = $this->get_object('Q210366'); $arr = $arr->entities->Q210366;}
        if($this->debug_taxon == "Rosa acicularis")             {$arr = $this->get_object('Q218368'); $arr = $arr->entities->Q218368;}
        if($this->debug_taxon == "Gavia pacifica")              {$arr = $this->get_object('Q558990'); $arr = $arr->entities->Q558990;}
        if($this->debug_taxon == "Zonotrichia leucophrys")      {$arr = $this->get_object('Q686673'); $arr = $arr->entities->Q686673;}
        if($this->debug_taxon == "Stenodus nelma")              {$arr = $this->get_object('Q1089398'); $arr = $arr->entities->Q1089398;}
        //lang zea
        if($this->debug_taxon == "Alcidae")             {$arr = $this->get_object('Q28294'); $arr = $arr->entities->Q28294;}
        if($this->debug_taxon == "Psittaciformes")      {$arr = $this->get_object('Q31431'); $arr = $arr->entities->Q31431;}
        if($this->debug_taxon == "Cacatuidae")          {$arr = $this->get_object('Q31448'); $arr = $arr->entities->Q31448;}
        if($this->debug_taxon == "Squamata")                {$arr = $this->get_object('Q122422'); $arr = $arr->entities->Q122422;}
        if($this->debug_taxon == "Anophthalmus hitleri")    {$arr = $this->get_object('Q139796'); $arr = $arr->entities->Q139796;}
        if($this->debug_taxon == "Ilex aquifolium")         {$arr = $this->get_object('Q192190'); $arr = $arr->entities->Q192190;}
        if($this->debug_taxon == "Anser rossii")            {$arr = $this->get_object('Q244320'); $arr = $arr->entities->Q244320;}
        if($this->debug_taxon == "Peramelemorphia")         {$arr = $this->get_object('Q244587'); $arr = $arr->entities->Q244587;}
        if($this->debug_taxon == "Acrobates pygmaeus")      {$arr = $this->get_object('Q613177'); $arr = $arr->entities->Q613177;}
        if($this->debug_taxon == "Streptopelia decaocto")   {$arr = $this->get_object('Q54696'); $arr = $arr->entities->Q54696;}
        if($this->debug_taxon == "Alcedo atthis")           {$arr = $this->get_object('Q79915'); $arr = $arr->entities->Q79915;}
        if($this->debug_taxon == "Merops apiaster")         {$arr = $this->get_object('Q170718'); $arr = $arr->entities->Q170718;}
        if($this->debug_taxon == "Anser albifrons")         {$arr = $this->get_object('Q172093'); $arr = $arr->entities->Q172093;}
        //lang bjn
        if($this->debug_taxon == "Cheloniidae") {$arr = $this->get_object('Q30143'); $arr = $arr->entities->Q30143;}
        if($this->debug_taxon == "Syzygium malaccense") {$arr = $this->get_object('Q31936'); $arr = $arr->entities->Q31936;}
        if($this->debug_taxon == "Zingiber officinale") {$arr = $this->get_object('Q35625'); $arr = $arr->entities->Q35625;}
        if($this->debug_taxon == "Soricidae") {$arr = $this->get_object('Q41631'); $arr = $arr->entities->Q41631;}
        if($this->debug_taxon == "Mirounga") {$arr = $this->get_object('Q185231'); $arr = $arr->entities->Q185231;}
        if($this->debug_taxon == "Nasalis larvatus") {$arr = $this->get_object('Q192029'); $arr = $arr->entities->Q192029;}
        if($this->debug_taxon == "Enterobius") {$arr = $this->get_object('Q213912'); $arr = $arr->entities->Q213912;}
        if($this->debug_taxon == "Phoebe") {$arr = $this->get_object('Q311714'); $arr = $arr->entities->Q311714;}
        if($this->debug_taxon == "Hemibagrus") {$arr = $this->get_object('Q584717'); $arr = $arr->entities->Q584717;}
        if($this->debug_taxon == "Aleurites moluccanus") {$arr = $this->get_object('Q1160961'); $arr = $arr->entities->Q1160961;}
        if($this->debug_taxon == "Hipposideros doriae") {$arr = $this->get_object('Q1761504'); $arr = $arr->entities->Q1761504;}
        if($this->debug_taxon == "Mangifera casturi") {$arr = $this->get_object('Q1779784'); $arr = $arr->entities->Q1779784;}
        if($this->debug_taxon == "Aglaia odorata") {$arr = $this->get_object('Q2674471'); $arr = $arr->entities->Q2674471;}
        //lang zh-classical
        if($this->debug_taxon == "Birgus latro") {$arr = $this->get_object('Q206070'); $arr = $arr->entities->Q206070;}
        if($this->debug_taxon == "Pacifastacus leniusculus") {$arr = $this->get_object('Q693000'); $arr = $arr->entities->Q693000;}
        if($this->debug_taxon == "Aegithalos caudatus") {$arr = $this->get_object('Q170831'); $arr = $arr->entities->Q170831;}
        if($this->debug_taxon == "Plantago asiatica") {$arr = $this->get_object('Q1193995'); $arr = $arr->entities->Q1193995;}
        if($this->debug_taxon == "Kandelia obovata") {$arr = $this->get_object('Q2641342'); $arr = $arr->entities->Q2641342;}
        if($this->debug_taxon == "Severe acute respiratory syndrome-related coronavirus") {$arr = $this->get_object('Q278567'); $arr = $arr->entities->Q278567;}
        if($this->debug_taxon == "Sepiida") {$arr = $this->get_object('Q184479'); $arr = $arr->entities->Q184479;}
        if($this->debug_taxon == "Mene maculata") {$arr = $this->get_object('Q326253'); $arr = $arr->entities->Q326253;}
        if($this->debug_taxon == "Eubalaena japonica") {$arr = $this->get_object('Q506604'); $arr = $arr->entities->Q506604;}
        //lang mwl
        if($this->debug_taxon == "Pertya glabrescens") {$arr = $this->get_object('Q15231699'); $arr = $arr->entities->Q15231699;}
        if($this->debug_taxon == "Theobroma cacao") {$arr = $this->get_object('Q42385'); $arr = $arr->entities->Q42385;}
        if($this->debug_taxon == "Coptis") {$arr = $this->get_object('Q135562'); $arr = $arr->entities->Q135562;}
        if($this->debug_taxon == "Silybum") {$arr = $this->get_object('Q1521997'); $arr = $arr->entities->Q1521997;}
        if($this->debug_taxon == "Acer sempervirens") {$arr = $this->get_object('Q1788044'); $arr = $arr->entities->Q1788044;}
        if($this->debug_taxon == "Myopias gigas") {$arr = $this->get_object('Q3688753'); $arr = $arr->entities->Q3688753;}
        if($this->debug_taxon == "Paratriaenops pauliani") {$arr = $this->get_object('Q3895323'); $arr = $arr->entities->Q3895323;}
        if($this->debug_taxon == "Ornithoglossum") {$arr = $this->get_object('Q5399625'); $arr = $arr->entities->Q5399625;}
        if($this->debug_taxon == "Alligatoridae") {$arr = $this->get_object('Q6184764'); $arr = $arr->entities->Q6184764;}
        if($this->debug_taxon == "Propebela miona") {$arr = $this->get_object('Q7250110'); $arr = $arr->entities->Q7250110;}
        if($this->debug_taxon == "Phaenosperma globosum") {$arr = $this->get_object('Q11089058'); $arr = $arr->entities->Q11089058;}
        if($this->debug_taxon == "xxx") {$arr = $this->get_object('Q756'); $arr = $arr->entities->Q756;}
        if($this->debug_taxon == "Guardiola") {$arr = $this->get_object('Q928671'); $arr = $arr->entities->Q928671;}
        if($this->debug_taxon == "Macropygia mackinlayi") {$arr = $this->get_object('Q1272510'); $arr = $arr->entities->Q1272510;}
        if($this->debug_taxon == "Gymnoschoenus") {$arr = $this->get_object('Q2711809'); $arr = $arr->entities->Q2711809;}
        if($this->debug_taxon == "Phragmipedium warszewiczianum") {$arr = $this->get_object('Q4046873'); $arr = $arr->entities->Q4046873;}
        if($this->debug_taxon == "Erioscele") {$arr = $this->get_object('Q5389506'); $arr = $arr->entities->Q5389506;}
        if($this->debug_taxon == "Ciconia") {$arr = $this->get_object('Q6471715'); $arr = $arr->entities->Q6471715;}
        if($this->debug_taxon == "Ithycythara septemcostata") {$arr = $this->get_object('Q7986899'); $arr = $arr->entities->Q7986899;}
        if($this->debug_taxon == "Lactarius occidentalis") {$arr = $this->get_object('Q10315361'); $arr = $arr->entities->Q10315361;}
        //lang tk
        if($this->debug_taxon == "Giraffa camelopardalis") {$arr = $this->get_object('Q15083'); $arr = $arr->entities->Q15083;}
        if($this->debug_taxon == "Haliaeetus albicilla") {$arr = $this->get_object('Q25438'); $arr = $arr->entities->Q25438;}
        if($this->debug_taxon == "Brassica oleracea") {$arr = $this->get_object('Q146212'); $arr = $arr->entities->Q146212;}
        if($this->debug_taxon == "Gossypium herbaceum") {$arr = $this->get_object('Q177615'); $arr = $arr->entities->Q177615;}
        if($this->debug_taxon == "Gyps fulvus") {$arr = $this->get_object('Q177856'); $arr = $arr->entities->Q177856;}
        if($this->debug_taxon == "Typha laxmannii") {$arr = $this->get_object('Q165439'); $arr = $arr->entities->Q165439;}
        if($this->debug_taxon == "Haliaeetus leucoryphus") {$arr = $this->get_object('Q271462'); $arr = $arr->entities->Q271462;}
        if($this->debug_taxon == "Aquila rapax") {$arr = $this->get_object('Q374141'); $arr = $arr->entities->Q374141;}
        if($this->debug_taxon == "Amanita vittadinii") {$arr = $this->get_object('Q922092'); $arr = $arr->entities->Q922092;}
        if($this->debug_taxon == "Euphorbia turczaninowii") {$arr = $this->get_object('Q10927731'); $arr = $arr->entities->Q10927731;}
        if($this->debug_taxon == "Malus domestica") {$arr = $this->get_object('Q18674606'); $arr = $arr->entities->Q18674606;}
        //lang szy
        if($this->debug_taxon == "Egretta garzetta") {$arr = $this->get_object('Q131709'); $arr = $arr->entities->Q131709;}
        if($this->debug_taxon == "Setaria italica") {$arr = $this->get_object('Q161211'); $arr = $arr->entities->Q161211;}
        if($this->debug_taxon == "Cinnamomum cassia") {$arr = $this->get_object('Q204148'); $arr = $arr->entities->Q204148;}
        if($this->debug_taxon == "Plumbago zeylanica") {$arr = $this->get_object('Q5240308'); $arr = $arr->entities->Q5240308;}
        if($this->debug_taxon == "Ixora chinensis") {$arr = $this->get_object('Q9010209'); $arr = $arr->entities->Q9010209;}
        if($this->debug_taxon == "Arenga engleri") {$arr = $this->get_object('Q11045924'); $arr = $arr->entities->Q11045924;}
        if($this->debug_taxon == "Elaeagnus oldhamii") {$arr = $this->get_object('Q11117470'); $arr = $arr->entities->Q11117470;}
        if($this->debug_taxon == "Diospyros strigosa") {$arr = $this->get_object('Q11127499'); $arr = $arr->entities->Q11127499;}
        if($this->debug_taxon == "Aristolochia zollingeriana") {$arr = $this->get_object('Q15245652'); $arr = $arr->entities->Q15245652;}
        if($this->debug_taxon == "Fragaria ×ananassa") {$arr = $this->get_object('Q13158'); $arr = $arr->entities->Q13158;}
        if($this->debug_taxon == "Chilopoda") {$arr = $this->get_object('Q43447'); $arr = $arr->entities->Q43447;}
        if($this->debug_taxon == "Areca catechu") {$arr = $this->get_object('Q156969'); $arr = $arr->entities->Q156969;}
        if($this->debug_taxon == "Hemerocallis citrina") {$arr = $this->get_object('Q4261552'); $arr = $arr->entities->Q4261552;}
        //lang tcy
        if($this->debug_taxon == "Testudinidae") {$arr = $this->get_object('Q46360'); $arr = $arr->entities->Q46360;}
        if($this->debug_taxon == "Alstonia scholaris") {$arr = $this->get_object('Q135307'); $arr = $arr->entities->Q135307;}
        if($this->debug_taxon == "Mentha arvensis") {$arr = $this->get_object('Q160585'); $arr = $arr->entities->Q160585;}
        if($this->debug_taxon == "Pycnonotidae") {$arr = $this->get_object('Q188854'); $arr = $arr->entities->Q188854;}
        if($this->debug_taxon == "Nelumbo") {$arr = $this->get_object('Q207427'); $arr = $arr->entities->Q207427;}
        if($this->debug_taxon == "Annona squamosa") {$arr = $this->get_object('Q275620'); $arr = $arr->entities->Q275620;}
        if($this->debug_taxon == "Pandanus odorifer") {$arr = $this->get_object('Q15578898'); $arr = $arr->entities->Q15578898;}
        //lang ab
        if($this->debug_taxon == "Staphylea colchica") {$arr = $this->get_object('Q2671147'); $arr = $arr->entities->Q2671147;}
        if($this->debug_taxon == "Citrus ×sinensis") {$arr = $this->get_object('Q3355098'); $arr = $arr->entities->Q3355098;}
        if($this->debug_taxon == "Solanum melongena") {$arr = $this->get_object('Q7540'); $arr = $arr->entities->Q7540;}
        if($this->debug_taxon == "Anas querquedula") {$arr = $this->get_object('Q25644'); $arr = $arr->entities->Q25644;}
        if($this->debug_taxon == "Aquila chrysaetos") {$arr = $this->get_object('Q41181'); $arr = $arr->entities->Q41181;}
        if($this->debug_taxon == "Prunus cerasifera") {$arr = $this->get_object('Q146951'); $arr = $arr->entities->Q146951;}
        if($this->debug_taxon == "Portulaca oleracea") {$arr = $this->get_object('Q158015'); $arr = $arr->entities->Q158015;}
        if($this->debug_taxon == "Diospyros") {$arr = $this->get_object('Q165258'); $arr = $arr->entities->Q165258;}
        if($this->debug_taxon == "Mareca penelope") {$arr = $this->get_object('Q28106837'); $arr = $arr->entities->Q28106837;}
        if($this->debug_taxon == "Solanum tuberosum") {$arr = $this->get_object('Q10998'); $arr = $arr->entities->Q10998;}
        if($this->debug_taxon == "Viola odorata") {$arr = $this->get_object('Q108684'); $arr = $arr->entities->Q108684;}
        if($this->debug_taxon == "Rhododendron luteum") {$arr = $this->get_object('Q2591210'); $arr = $arr->entities->Q2591210;}
        //lang wo
        if($this->debug_taxon == "Mus") {$arr = $this->get_object('Q39275'); $arr = $arr->entities->Q39275;}
        if($this->debug_taxon == "Abutilon") {$arr = $this->get_object('Q132638'); $arr = $arr->entities->Q132638;}
        if($this->debug_taxon == "Ceiba pentandra") {$arr = $this->get_object('Q138617'); $arr = $arr->entities->Q138617;}
        if($this->debug_taxon == "Ficus elastica") {$arr = $this->get_object('Q160576'); $arr = $arr->entities->Q160576;}
        if($this->debug_taxon == "Psidium guajava") {$arr = $this->get_object('Q166843'); $arr = $arr->entities->Q166843;}
        if($this->debug_taxon == "Delphinus delphis") {$arr = $this->get_object('Q207114'); $arr = $arr->entities->Q207114;}
        if($this->debug_taxon == "Crateva religiosa") {$arr = $this->get_object('Q311133'); $arr = $arr->entities->Q311133;}
        if($this->debug_taxon == "Adenium obesum") {$arr = $this->get_object('Q311994'); $arr = $arr->entities->Q311994;}
        if($this->debug_taxon == "Balanites aegyptiaca") {$arr = $this->get_object('Q606704'); $arr = $arr->entities->Q606704;}
        if($this->debug_taxon == "Oxytenanthera abyssinica") {$arr = $this->get_object('Q641048'); $arr = $arr->entities->Q641048;}
        if($this->debug_taxon == "Combretum micranthum") {$arr = $this->get_object('Q1245859'); $arr = $arr->entities->Q1245859;}
        if($this->debug_taxon == "Vitellaria paradoxa") {$arr = $this->get_object('Q1348567'); $arr = $arr->entities->Q1348567;}
        if($this->debug_taxon == "Pseudotolithus") {$arr = $this->get_object('Q2111255'); $arr = $arr->entities->Q2111255;}
        if($this->debug_taxon == "Lutjanus agennes") {$arr = $this->get_object('Q2233181'); $arr = $arr->entities->Q2233181;}
        if($this->debug_taxon == "Prosopis africana") {$arr = $this->get_object('Q12066488'); $arr = $arr->entities->Q12066488;}
        //lang ay
        if($this->debug_taxon == "Ananas comosus") {$arr = $this->get_object('Q1493'); $arr = $arr->entities->Q1493;}
        if($this->debug_taxon == "Strigiformes") {$arr = $this->get_object('Q25222'); $arr = $arr->entities->Q25222;}
        if($this->debug_taxon == "Ipomoea batatas") {$arr = $this->get_object('Q37937'); $arr = $arr->entities->Q37937;}
        if($this->debug_taxon == "Lama glama") {$arr = $this->get_object('Q42569'); $arr = $arr->entities->Q42569;}
        if($this->debug_taxon == "Dasypodidae") {$arr = $this->get_object('Q47867'); $arr = $arr->entities->Q47867;}
        if($this->debug_taxon == "Vultur gryphus") {$arr = $this->get_object('Q170598'); $arr = $arr->entities->Q170598;}
        if($this->debug_taxon == "Accipiter") {$arr = $this->get_object('Q188737'); $arr = $arr->entities->Q188737;}
        if($this->debug_taxon == "Rupicola peruvianus") {$arr = $this->get_object('Q490393'); $arr = $arr->entities->Q490393;}
        //lang tyv
        if($this->debug_taxon == "Otocolobus manul") {$arr = $this->get_object('Q166794'); $arr = $arr->entities->Q166794;}
        if($this->debug_taxon == "Fomes fomentarius") {$arr = $this->get_object('Q229814'); $arr = $arr->entities->Q229814;}
        if($this->debug_taxon == "Pyrus") {$arr = $this->get_object('Q434'); $arr = $arr->entities->Q434;}
        if($this->debug_taxon == "Bryophyta") {$arr = $this->get_object('Q25347'); $arr = $arr->entities->Q25347;}
        if($this->debug_taxon == "Abies") {$arr = $this->get_object('Q25350'); $arr = $arr->entities->Q25350;}
        if($this->debug_taxon == "Anethum graveolens") {$arr = $this->get_object('Q26686'); $arr = $arr->entities->Q26686;}
        if($this->debug_taxon == "Daucus") {$arr = $this->get_object('Q27949'); $arr = $arr->entities->Q27949;}
        if($this->debug_taxon == "Malus") {$arr = $this->get_object('Q104819'); $arr = $arr->entities->Q104819;}
        //lang mg
        if($this->debug_taxon == "Bos primigenius indicus") {$arr = $this->get_object('Q46889'); $arr = $arr->entities->Q46889;}
        if($this->debug_taxon == "Spheniscidae") {$arr = $this->get_object('Q9147'); $arr = $arr->entities->Q9147;}
        if($this->debug_taxon == "Numididae") {$arr = $this->get_object('Q171953'); $arr = $arr->entities->Q171953;}
        if($this->debug_taxon == "Cryptoprocta ferox") {$arr = $this->get_object('Q183330'); $arr = $arr->entities->Q183330;}
        if($this->debug_taxon == "Brookesia micra") {$arr = $this->get_object('Q232278'); $arr = $arr->entities->Q232278;}
        if($this->debug_taxon == "Eulemur mongoz") {$arr = $this->get_object('Q543695'); $arr = $arr->entities->Q543695;}
        if($this->debug_taxon == "Indri indri") {$arr = $this->get_object('Q203868'); $arr = $arr->entities->Q203868;}
        if($this->debug_taxon == "Annona squamosa") {$arr = $this->get_object('Q275620'); $arr = $arr->entities->Q275620;}
        if($this->debug_taxon == "Cuculus rochii") {$arr = $this->get_object('Q631863'); $arr = $arr->entities->Q631863;}
        if($this->debug_taxon == "Harveya") {$arr = $this->get_object('Q5677738'); $arr = $arr->entities->Q5677738;}
        //lang rm
        if($this->debug_taxon == "Dianthus carthusianorum") {$arr = $this->get_object('Q159735'); $arr = $arr->entities->Q159735;}
        if($this->debug_taxon == "Daphne mezereum") {$arr = $this->get_object('Q165166'); $arr = $arr->entities->Q165166;}
        if($this->debug_taxon == "Coturnix chinensis") {$arr = $this->get_object('Q244429'); $arr = $arr->entities->Q244429;}
        if($this->debug_taxon == "Marsupialia") {$arr = $this->get_object('Q25336'); $arr = $arr->entities->Q25336;}
        if($this->debug_taxon == "Fagus sylvatica") {$arr = $this->get_object('Q146149'); $arr = $arr->entities->Q146149;}
        if($this->debug_taxon == "Leontopodium nivale") {$arr = $this->get_object('Q178265'); $arr = $arr->entities->Q178265;}
        if($this->debug_taxon == "Arnica montana") {$arr = $this->get_object('Q207848'); $arr = $arr->entities->Q207848;}
        if($this->debug_taxon == "Microchiroptera") {$arr = $this->get_object('Q971343'); $arr = $arr->entities->Q971343;}
        if($this->debug_taxon == "Phasianidae") {$arr = $this->get_object('Q26375'); $arr = $arr->entities->Q26375;}
        if($this->debug_taxon == "Acer platanoides") {$arr = $this->get_object('Q26745'); $arr = $arr->entities->Q26745;}
        //lang ltg
        if($this->debug_taxon == "Puma yagouaroundi") {$arr = $this->get_object('Q182304'); $arr = $arr->entities->Q182304;}
        if($this->debug_taxon == "Felis nigripes") {$arr = $this->get_object('Q204814'); $arr = $arr->entities->Q204814;}
        if($this->debug_taxon == "Leopardus tigrinus") {$arr = $this->get_object('Q205948'); $arr = $arr->entities->Q205948;}
        if($this->debug_taxon == "Buprestidae") {$arr = $this->get_object('Q503892'); $arr = $arr->entities->Q503892;}
        if($this->debug_taxon == "Hirundinidae") {$arr = $this->get_object('Q39861'); $arr = $arr->entities->Q39861;}
        if($this->debug_taxon == "Carpinus betulus") {$arr = $this->get_object('Q158776'); $arr = $arr->entities->Q158776;}
        if($this->debug_taxon == "Lynx canadensis") {$arr = $this->get_object('Q146457'); $arr = $arr->entities->Q146457;}
        if($this->debug_taxon == "Profelis aurata") {$arr = $this->get_object('Q192231'); $arr = $arr->entities->Q192231;}
        if($this->debug_taxon == "Prionailurus planiceps") {$arr = $this->get_object('Q274177'); $arr = $arr->entities->Q274177;}
        if($this->debug_taxon == "Neofelis diardi") {$arr = $this->get_object('Q275163'); $arr = $arr->entities->Q275163;}
        //lang kl
        if($this->debug_taxon == "Monodon monoceros") {$arr = $this->get_object('Q159426'); $arr = $arr->entities->Q159426;}
        if($this->debug_taxon == "Rhodiola rosea") {$arr = $this->get_object('Q161665'); $arr = $arr->entities->Q161665;}
        if($this->debug_taxon == "Leporidae") {$arr = $this->get_object('Q25900'); $arr = $arr->entities->Q25900;}
        if($this->debug_taxon == "Branta leucopsis") {$arr = $this->get_object('Q26680'); $arr = $arr->entities->Q26680;}
        if($this->debug_taxon == "Nematocera") {$arr = $this->get_object('Q27605'); $arr = $arr->entities->Q27605;}
        if($this->debug_taxon == "Hippopotamus amphibius") {$arr = $this->get_object('Q34505'); $arr = $arr->entities->Q34505;}
        if($this->debug_taxon == "Odobenus rosmarus") {$arr = $this->get_object('Q40994'); $arr = $arr->entities->Q40994;}
        if($this->debug_taxon == "Canis lupus arctos") {$arr = $this->get_object('Q216441'); $arr = $arr->entities->Q216441;}
        if($this->debug_taxon == "Branta bernicla") {$arr = $this->get_object('Q27050'); $arr = $arr->entities->Q27050;}
        if($this->debug_taxon == "Physeter macrocephalus") {$arr = $this->get_object('Q81214'); $arr = $arr->entities->Q81214;}
        if($this->debug_taxon == "Salvelinus alpinus") {$arr = $this->get_object('Q421047'); $arr = $arr->entities->Q421047;}
        if($this->debug_taxon == "Gossypium") {$arr = $this->get_object('Q719312'); $arr = $arr->entities->Q719312;}
        if($this->debug_taxon == "Vermilingua") {$arr = $this->get_object('Q972452'); $arr = $arr->entities->Q972452;}
        if($this->debug_taxon == "Somniosus microcephalus") {$arr = $this->get_object('Q370444'); $arr = $arr->entities->Q370444;}
        if($this->debug_taxon == "Sorbus groenlandica") {$arr = $this->get_object('Q17240531'); $arr = $arr->entities->Q17240531;}
        //lang dty
        if($this->debug_taxon == "Clangula hyemalis") {$arr = $this->get_object('Q26597'); $arr = $arr->entities->Q26597;}
        if($this->debug_taxon == "Portulaca grandiflora") {$arr = $this->get_object('Q159547'); $arr = $arr->entities->Q159547;}
        if($this->debug_taxon == "Ixobrychus cinnamomeus") {$arr = $this->get_object('Q204111'); $arr = $arr->entities->Q204111;}
        if($this->debug_taxon == "Ardeola grayii") {$arr = $this->get_object('Q498428'); $arr = $arr->entities->Q498428;}
        if($this->debug_taxon == "Mycteria leucocephala") {$arr = $this->get_object('Q631805'); $arr = $arr->entities->Q631805;}
        if($this->debug_taxon == "Gyps tenuirostris") {$arr = $this->get_object('Q862806'); $arr = $arr->entities->Q862806;}
        if($this->debug_taxon == "Aythya ferina") {$arr = $this->get_object('Q26742'); $arr = $arr->entities->Q26742;}
        if($this->debug_taxon == "Carica papaya") {$arr = $this->get_object('Q34887'); $arr = $arr->entities->Q34887;}
        if($this->debug_taxon == "Aethopyga siparaja") {$arr = $this->get_object('Q73760'); $arr = $arr->entities->Q73760;}
        if($this->debug_taxon == "Aquila nipalensis") {$arr = $this->get_object('Q179359'); $arr = $arr->entities->Q179359;}
        if($this->debug_taxon == "Pelecanus philippensis") {$arr = $this->get_object('Q180360'); $arr = $arr->entities->Q180360;}
        if($this->debug_taxon == "Lophophorus impejanus") {$arr = $this->get_object('Q742818'); $arr = $arr->entities->Q742818;}
        if($this->debug_taxon == "Ixobrychus flavicollis") {$arr = $this->get_object('Q888559'); $arr = $arr->entities->Q888559;}
        if($this->debug_taxon == "Lagerstroemia indica") {$arr = $this->get_object('Q1148692'); $arr = $arr->entities->Q1148692;}
        if($this->debug_taxon == "Turdoides affinis") {$arr = $this->get_object('Q1590643'); $arr = $arr->entities->Q1590643;}
        //lang rn
        if($this->debug_taxon == "Lamprichthys tanganicanus") {$arr = $this->get_object('Q129639'); $arr = $arr->entities->Q129639;}
        if($this->debug_taxon == "Python sebae") {$arr = $this->get_object('Q764797'); $arr = $arr->entities->Q764797;}
        if($this->debug_taxon == "Pan troglodytes") {$arr = $this->get_object('Q4126704'); $arr = $arr->entities->Q4126704;}
        if($this->debug_taxon == "Otiophora rupicola") {$arr = $this->get_object('Q15481439'); $arr = $arr->entities->Q15481439;}
        if($this->debug_taxon == "Habenaria lewallei") {$arr = $this->get_object('Q15482441'); $arr = $arr->entities->Q15482441;}
        if($this->debug_taxon == "Acampe cephalotes") {$arr = $this->get_object('Q15500941'); $arr = $arr->entities->Q15500941;}
        if($this->debug_taxon == "Aa fiebrigii") {$arr = $this->get_object('Q15588968'); $arr = $arr->entities->Q15588968;}
        if($this->debug_taxon == "Lycaon pictus") {$arr = $this->get_object('Q173651'); $arr = $arr->entities->Q173651;}
        if($this->debug_taxon == "Redunca redunca") {$arr = $this->get_object('Q548442'); $arr = $arr->entities->Q548442;}
        if($this->debug_taxon == "Acampe rigida") {$arr = $this->get_object('Q3336674'); $arr = $arr->entities->Q3336674;}
        if($this->debug_taxon == "Drymaria") {$arr = $this->get_object('Q3802353'); $arr = $arr->entities->Q3802353;}
        if($this->debug_taxon == "Polystachya couloniana") {$arr = $this->get_object('Q15444007'); $arr = $arr->entities->Q15444007;}
        if($this->debug_taxon == "Diaphananthe arbonnieri") {$arr = $this->get_object('Q15449289'); $arr = $arr->entities->Q15449289;}
        if($this->debug_taxon == "Bulbostylis nemoides") {$arr = $this->get_object('Q15560927'); $arr = $arr->entities->Q15560927;}
        return $arr;
    }
}
/* testing nuke html
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Acacia'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Bald Eagle'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Rosa'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Fungi'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Aves'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Panthera tigris'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'sunflower'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Hominidae'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Coronaviridae'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Tracheophyta'

php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Anura'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Istiophorus'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Bubulcus ibis'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Senna alexandrina'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Typha angustifolia'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Metroxylon sagu'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Cananga odorata'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Geopelia striata'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Lutjanus vitta'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Phyllanthus emblica'

php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'Chanos chanos'
php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'Oreochromis niloticus'
php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'Polar bear'
php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'Angiosperms'
php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'Mus musculus'
php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'Rodentia'
php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'Animalia'
php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'Plantae'
php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'Virus'
php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'ferns'
*/
?>