<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Library of API interface for block_pegase.
 *
 * @package     block_pegase
 * @copyright   2026 Olivier VALENTIN
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_pegase;

defined('MOODLE_INTERNAL') || die();

class api {

    /** @var string CAS auth URL */
    private string $authurl;

    /** @var string CHC API base URL */
    private string $apiurl;

    /** @var string ODF API base URL */
    private string $odfurl;

    /** @var string API username */
    private string $username;

    /** @var string API password */
    private string $password;

    /** @var string|null Bearer token cached for request lifetime */
    private ?string $token = null;

    /**
     * Constructor — loads config from block settings.
     *
     * @throws \moodle_exception If required settings are missing
     */
    public function __construct() {
        $this->authurl  = get_config('block_pegase', 'authurl');
        $this->apiurl   = get_config('block_pegase', 'apiurl');
        $this->odfurl   = get_config('block_pegase', 'odfurl');
        $this->username = get_config('block_pegase', 'username');
        $this->password = get_config('block_pegase', 'password');

        if (empty($this->authurl) || empty($this->apiurl) || empty($this->odfurl)
                || empty($this->username) || empty($this->password)) {
            throw new \moodle_exception('invalidtoken', 'block_pegase');
        }
    }

    // =========================================================================
    // AUTHENTICATION
    // =========================================================================

    /**
     * Retrieves a Bearer token from PEGASE CAS.
     * Uses internal cache — only one call per instance lifetime.
     *
     * DO NOT USE http_build_query() with this CAS server — use raw string.
     *
     * @return string Bearer token
     * @throws \moodle_exception If authentication fails
     */
    private function get_token(): string {

        if ($this->token !== null) {
            return $this->token;
        }

        $post_fields = 'username=' . urlencode($this->username)
                     . '&password=' . urlencode($this->password)
                     . '&token=true';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->authurl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $post_fields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \moodle_exception('apiunavailable', 'block_pegase', '', $error);
        }

        $httpcode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $body = trim(substr($response, $header_size));

        if ($httpcode !== 201 || empty($body)) {
            throw new \moodle_exception('invalidtoken', 'block_pegase');
        }

        $this->token = $body;
        return $this->token;
    }

    // =========================================================================
    // CHC API CALLS
    // =========================================================================

    /**
     * Performs an authenticated GET request to the CHC API.
     * Security rule: always throws on error — never returns empty silently
     * to avoid accidental unenrolments.
     *
     * @param string $endpoint Relative path to append to apiurl
     * @return array Decoded JSON data
     * @throws \moodle_exception
     */
    private function call(string $endpoint): array {

        $token = $this->get_token();
        $url   = $this->apiurl . $endpoint;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \moodle_exception('apiunavailable', 'block_pegase', '', $error);
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode < 200 || $httpcode >= 300) {
            throw new \moodle_exception('apierror', 'block_pegase', '', "HTTP $httpcode on $url");
        }

        if (empty($response)) {
            throw new \moodle_exception('apiunavailable', 'block_pegase');
        }

        $data = json_decode($response, true);

        if ($data === null) {
            throw new \moodle_exception('apierror', 'block_pegase', '', 'Invalid JSON response');
        }

        return $data;
    }

    // =========================================================================
    // ODF API CALLS
    // =========================================================================

    /**
     * Performs an authenticated GET request to the ODF API.
     * Same security rules as call().
     *
     * @param string $endpoint Relative path to append to odfurl
     * @return array Decoded JSON data
     * @throws \moodle_exception
     */
    private function call_odf(string $endpoint): array {

        $token = $this->get_token();
        $url   = $this->odfurl . $endpoint;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \moodle_exception('apiunavailable', 'block_pegase', '', $error);
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode < 200 || $httpcode >= 300) {
            throw new \moodle_exception('apierror', 'block_pegase', '', "HTTP $httpcode on $url");
        }

        if (empty($response)) {
            throw new \moodle_exception('apiunavailable', 'block_pegase');
        }

        $data = json_decode($response, true);

        if ($data === null) {
            throw new \moodle_exception('apierror', 'block_pegase', '', 'Invalid JSON response');
        }

        return $data;
    }

    // =========================================================================
    // BUSINESS METHODS — CHC
    // =========================================================================

    /**
     * Retrieves students enrolled in an EC for a given period.
     * Deduplicates students across multiple groups of the same EC.
     *
     * @param string $code_structure  Establishment code (ex: ETAB00)
     * @param string $code_periode    Period (ex: PERIODE-25-26)
     * @param string $code_ec         EC code (ex: Y4SDU513)
     * @return array {
     *     title: string,
     *     object_type: string,
     *     object_type_label: string,
     *     students: array
     * }
     * @throws \moodle_exception
     */
    public function get_apprenants(string $code_structure, string $code_periode, string $code_ec): array {

        $endpoint = '/objet-formation/'
                  . urlencode($code_structure) . '/'
                  . urlencode($code_periode) . '/'
                  . urlencode($code_ec) . '/cursus';

        $data = $this->call($endpoint);

        $students          = [];
        $title             = '';
        $object_type       = '';
        $object_type_label = '';

        foreach ($data as $group) {
            // Retrieve training object info from first group
            if (empty($title) && !empty($group['objetFormation'])) {
                $title             = $group['objetFormation']['libelleLong'] ?? '';
                $object_type       = $group['objetFormation']['typeObjetFormation']['code'] ?? '';
                $object_type_label = $group['objetFormation']['typeObjetFormation']['libelleLong'] ?? '';
            }

            if (!empty($group['listeApprenants'])) {
                foreach ($group['listeApprenants'] as $student) {
                    // Deduplicate by student code
                    $students[$student['codeApprenant']] = $student;
                }
            }
        }

        return [
            'title'             => $title,
            'object_type'       => $object_type,
            'object_type_label' => $object_type_label,
            'students'          => array_values($students),
        ];
    }

    /**
     * Tests connection to PEGASE APIs.
     * Used in settings or to validate config before sync.
     *
     * @return bool true if connection works
     * @throws \moodle_exception If connection fails
     */
    public function test_connection(): bool {
        $this->get_token();
        return true;
    }

    // =========================================================================
    // BUSINESS METHODS — ODF
    // =========================================================================

    /**
     * Searches for training programs matching a keyword.
     * Calls: /etablissement/{codeStructure}/objets-maquette
     *
     * @param string $code_structure  Establishment code
     * @param string $keyword         Search keyword (code or label)
     * @param int    $page            Page number (default 0)
     * @param int    $page_size       Results per page (default 20)
     * @return array {items, total_elements, total_pages, page}
     * @throws \moodle_exception
     */
    public function search_formations(string $code_structure, string $keyword, string $espace_id = '', int $page = 0, int $page_size = 20): array {

        // DO NOT USE http_build_query() for multiple same-name params
        $params = 'page=' . $page
            . '&taille=' . $page_size
            . '&r=' . urlencode($keyword)
            . '&typeObjetMaquette=FORMATION';  // FORMATION, not OBJET-FORMATION !!!!!!!!!!!!!!!!!

        // Filter by period UUID if provided
        if (!empty($espace_id)) {
            $params .= '&espace=' . urlencode($espace_id);
        }

        $endpoint = '/etablissement/' . urlencode($code_structure) . '/objets-maquette?' . $params;
        $data     = $this->call_odf($endpoint);

        return [
            'items'          => $data['items']        ?? [],
            'total_elements' => $data['totalElements'] ?? 0,
            'total_pages'    => $data['totalPages']    ?? 0,
            'page'           => $data['page']          ?? 0,
        ];
    }

    /**
     * Retrieves the full structure (tree) of a training program by its ID.
     * Calls: /etablissement/{codeStructure}/maquette/{id}
     *
     * @param string $code_structure  Establishment code
     * @param string $formation_id    Training program UUID
     * @return array Full tree with semesters, UEs and ECs
     * @throws \moodle_exception
     */
    public function get_formation_tree(string $code_structure, string $formation_id): array {

        $endpoint = '/etablissement/' . urlencode($code_structure)
                  . '/maquette/'     . urlencode($formation_id);

        $data = $this->call_odf($endpoint);

        return $data['racine'] ?? [];
    }

    public function get_espaces(string $code_structure): array {

        $periods  = [];
        $page     = 0;
        $per_page = 50;

        do {
            // Use API filters directly
            $params   = 'page=' . $page
                    . '&taille=' . $per_page
                    . '&type=PERIODE'
                    . '&actif=true';

            $endpoint = '/etablissement/' . urlencode($code_structure) . '/espaces?' . $params;
            $data     = $this->call_odf($endpoint);

            foreach ($data['items'] ?? [] as $item) {
                $periods[] = [
                    'id'      => $item['id'],      // UUID — needed for browse search
                    'code'    => $item['code'],    // PERIODE-25-26 — needed for CHC API
                    'libelle' => $item['libelleLong'] ?? $item['libelle'] ?? $item['code'],
                    'annee'   => $item['anneeUniversitaire'] ?? 0,
                ];
            }

            $total_pages = $data['totalPages'] ?? 1;
            $page++;

        } while ($page < $total_pages);

        // Most recent first
        usort($periods, fn($a, $b) => $b['annee'] <=> $a['annee']);

        return $periods;
    }

}
