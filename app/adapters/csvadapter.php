<?php

//
//  Copyright (C) 2014 by Jackie Ng
//
//  This library is free software; you can redistribute it and/or
//  modify it under the terms of version 2.1 of the GNU Lesser
//  General Public License as published by the Free Software Foundation.
//
//  This library is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//  Lesser General Public License for more details.
//
//  You should have received a copy of the GNU Lesser General Public
//  License along with this library; if not, write to the Free Software
//  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
//

require_once "restadapter.php";

class MgCsvRestAdapter extends MgFeatureRestAdapter
{
    private $agfRw;
    private $wktRw;

    private $read;

    public function __construct($app, $siteConn, $resId, $className, $config, $configPath, $featureIdProp) {
        $this->read = 0;
        parent::__construct($app, $siteConn, $resId, $className, $config, $configPath, $featureIdProp);
    }

    private function CsvEscape($str) {
        return str_replace("\"", '\\"', $str);
    }

    /**
     * Initializes the adapater with the given REST configuration
     */
    protected function InitAdapterConfig($config) {
        
    }

    /**
     * Writes the GET response header based on content of the given MgReader
     */
    protected function GetResponseBegin($reader) {
        $this->agfRw = new MgAgfReaderWriter();
        $this->wktRw = new MgWktReaderWriter();

        $this->app->response->header("Content-Type", "text/csv");
        $values = array();
        $propCount = $reader->GetPropertyCount();
        for ($i = 0; $i < $propCount; $i++) {
            $name = $reader->GetPropertyName($i);
            array_push($values, $this->CsvEscape($name));
        }
        $this->app->response->write("\"".implode("\",\"", $values)."\"\n");
    }

    /**
     * Returns true if the current reader iteration loop should continue, otherwise the loop is broken
     */
    protected function GetResponseShouldContinue($reader) {
        $this->read++;
        $result = !($this->limit > 0 && $this->read > $this->limit);
        return $result;
    }

    /**
     * Writes the GET response body based on the current record of the given MgReader. The caller must not advance to the next record
     * in the reader while inside this method
     */
    protected function GetResponseBodyRecord($reader) {
        $values = array();
        $propCount = $reader->GetPropertyCount();
        for ($i = 0; $i < $propCount; $i++) {
            $name = $reader->GetPropertyName($i);
            $propType = $reader->GetPropertyType($i);
            
            if (!$reader->IsNull($i)) {
                switch($propType) {
                    case MgPropertyType::Boolean:
                        array_push($values, $this->CsvEscape($reader->GetBoolean($i)));
                        break;
                    case MgPropertyType::Byte:
                        array_push($values, $this->CsvEscape($reader->GetByte($i)));
                        break;
                    case MgPropertyType::DateTime:
                        $dt = $reader->GetDateTime($i);
                        array_push($values, $this->CsvEscape($dt->ToString()));
                        break;
                    case MgPropertyType::Decimal:
                    case MgPropertyType::Double:
                        array_push($values, $this->CsvEscape($reader->GetDouble($i)));
                        break;
                    case MgPropertyType::Geometry:
                        {
                            try {
                                $agf = $reader->GetGeometry($i);
                                $geom = ($this->transform != null) ? $this->agfRw->Read($agf, $this->transform) : $this->agfRw->Read($agf);
                                array_push($values, $this->CsvEscape($this->wktRw->Write($geom)));
                            } catch (MgException $ex) {

                            }
                        }
                        break;
                    case MgPropertyType::Int16:
                        array_push($values, $this->CsvEscape($reader->GetInt16($i)));
                        break;
                    case MgPropertyType::Int32:
                        array_push($values, $this->CsvEscape($reader->GetInt32($i)));
                        break;
                    case MgPropertyType::Int64:
                        array_push($values, $this->CsvEscape($reader->GetInt64($i)));
                        break;
                    case MgPropertyType::Single:
                        array_push($values, $this->CsvEscape($reader->GetSingle($i)));
                        break;
                    case MgPropertyType::String:
                        array_push($values, $this->CsvEscape($reader->GetString($i)));
                        break;
                }
            } else {
                array_push($values, "");    
            }
        }
        $this->app->response->write("\"".implode("\",\"", $values)."\"\n");
    }

    /**
     * Writes the GET response ending based on content of the given MgReader
     */
    protected function GetResponseEnd($reader) {

    }
}

?>