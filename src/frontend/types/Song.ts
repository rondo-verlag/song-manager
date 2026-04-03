export interface SongFile {
	id: number
	songId: number
	name: string
	mime: string
	type: 'copyright'
	filesize: number
	creationTime: string
}

export interface Song {
	id: string
	title: string
	interpret: string
	license: string
	status: string
	alternativeTitles?: string
	text?: string
	mood?: string
	lang?: 'de' | 'ch' | 'fr' | 'it' | 'en' | 'other' | null
	pageRondoRed?: string
	pageRondoBlue?: string
	pageRondoGreen?: string
	pageRondo2017?: string
	pageRondo2021?: string
	pageRondo2024?: string
	copyrightInfoApp?: string
	copyrightInfoBook?: string
	copyrightPublisher?: string
	copyrightContact?: string
	copyrightStatusApp?: string
	copyrightStatusBook2017?: string
	copyrightStatusBook2021?: string
	copyrightStatusBook2024?: string
	copyrightCommentApp?: string
	license_type?: string
	license_type_app?: string
	licenseAppUntil?: string
	licenseAppUntilIndefinite?: number
	releaseApp2017?: number
	releaseApp2022?: number
	releaseApp2024?: number
	releaseBook2017?: number
	releaseBook2021?: number
	releaseBook2024?: number
	youtubeLink?: string
	comments?: string
	rawImageSize?: number
	rawMidiSize?: number
	rawNotesPDFSize?: number
	rawSIBSize?: number
	rawXMLSize?: number
	hasImage?: number
	hasNotesPDF?: number
	hasMidi?: number
	files?: SongFile[]
}
