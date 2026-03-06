<?php
/**
 * Partial: Houses – tabela wymagania ustawowe
 * Path: template-parts/sections/houses-table.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="houses__table-wrap">
	<div class="houses__table-inner">

		<div class="houses__table-head">
			<h2 class="section-title">Lista lokali</h2>
		</div>

		<div class="houses__table-scroll" role="region" aria-label="Tabela lokali" tabindex="0">
			<table class="houses__table" data-houses-table>
				<thead>
					<tr>
						<th scope="col">Oznaczenie domu (działka)</th>
						<th scope="col">Powierzchnia lokalu</th>
						<th scope="col">Liczba pokoi</th>
						<th scope="col">Dostępność</th>
						<th scope="col">Powierzchnia działki</th>
						<th scope="col">Cena</th>
						<th scope="col">Karta lokalu</th>
					</tr>
				</thead>
				<tbody data-houses-table-body>
					<tr>
						<td colspan="7">Ładowanie…</td>
					</tr>
				</tbody>
			</table>
		</div>

	</div>
</div>